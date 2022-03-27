<?php

namespace minga\framework;

use minga\framework\locking\QueueLock;
use minga\framework\locking\QueueProcessLock;

abstract class Queue
{
	protected string $path = '';
	protected bool $discardSuccessfullLog;
	protected int $maxToProcess;
	protected int $clearLogOlderThanDays = 60;

	protected string $processorClass = '';

	abstract public function __construct();

	public static function Enabled() : bool
	{
		if(Context::Settings()->isTesting)
			return false;
		return Context::Settings()->Queue()->Enabled;
	}

	protected function Initialize(string $path, int $maxToProcess = 50, bool $discardSuccessfullLog = false) : void
	{
		$this->maxToProcess = $maxToProcess;

		$this->path = $path;
		$this->discardSuccessfullLog = $discardSuccessfullLog;
	}

	public static function AddToQueueDb(string $function, ...$args) : int
	{
		if (Context::Settings()->Db()->NoDb)
			return 0;
		return self::AddToQueue($function, ...$args);
	}

	public static function AddToQueue(string $function, ...$args) : int
	{
		$queue = new static();
		$queue->Add($function, ...$args);
		return 1;
	}

	public function Add(string $function, ...$params) : void
	{
		if(self::Enabled() == false)
		{
			$this->ProcessItem($function, $params);
			return;
		}

		$this->CreateFile([
			'function' => $function,
			'params' => $params,
			// 'debug' => Log::FormatTraceLog(debug_backtrace()),
		]);
	}

	private function GetQueuePath(string $subPath) : string
	{
		$ret = Context::Paths()->GetQueuePath() . '/' . $this->path . '/' . $subPath;
		IO::EnsureExists($ret);
		return $ret;
	}

	protected function Call(string $className, string $function, array $params)
	{
		$method = [new $className(), $function];
		$params = $this->InstanciateParams($method, $params);
		return Reflection::CallMethod($method, ...$params);
	}

	private function InstanciateParams(array $method, array $params) : array
	{
		for($i = 0; $i < count($params); $i++)
		{
			$type = Reflection::GetParamType($method, $i);
			if($type == null)
				continue;
			$interfaces = class_implements($type);
			if(in_array(ArrayConstructable::class, $interfaces) == false)
				throw new ErrorException('No se pueden ejecutar métodos con parámetros de tipos que no implementan ArrayConstructable');

			$params[$i] = Reflection::InstanciateClass($type, $params[$i]);
		}
		return $params;
	}

	private function MoveToPath(string $file, string $path) : string
	{
		$target = $this->GetQueuePath($path) . '/' . basename(basename($file));
		if (file_exists($target))
			$target = IO::GetUniqueNameNoReplaceFilename($target);
		IO::Move($file, $target);
		return $target;
	}

	private function MoveToSuccess(string $file) : string
	{
		if ($this->discardSuccessfullLog)
		{
			IO::Delete($file);
			return '';
		}
		return $this->MoveToPath($file, 'success');
	}

	private function MoveToFailed(string $file) : string
	{
		return $this->MoveToPath($file, 'failed');
	}

	private function MoveToRunning(string $file) : string
	{
		return $this->MoveToPath($file, 'running');
	}

	private function doProcess(string $file) : bool
	{
		$text = file_get_contents($file);
		$data = json_decode($text, true, 512, JSON_INVALID_UTF8_SUBSTITUTE);
		if(is_array($data) == false || isset($data['function']) == false)
		{
			$finalPlace = $this->MoveToFailed($file);
			$this->SaveException($finalPlace . '.error.txt', new ErrorException('Archivo vacío o malformado.'));
			return false;
		}
		try
		{
			$this->ProcessItem($data['function'], $data['params']);
			$this->MoveToSuccess($file);
			return true;
		}
		catch(\Exception $ex)
		{
			$finalPlace = $this->MoveToFailed($file);
			$this->SaveException($finalPlace . '.error.txt', $ex);
			return false;
		}
	}

	private function EnsureQueuePath() : void
	{
		$this->GetQueuePath('queued');
	}

	public function Process() : array
	{
		$this->EnsureQueuePath();

		$lock = new QueueProcessLock($this->path);

		$lock->LockWrite();

		if ($this->clearLogOlderThanDays > 0 && rand(1, 100) === 1)
		{
			$clean = $this->GetQueuePath('success');
			IO::ClearFilesOlderThan($clean, $this->clearLogOlderThanDays);
		}

		$total = 0;

		$max = $this->maxToProcess;
		$done = 0;
		$failed = 0;
		$sucess = 0;
		$total = -1;
		for($i = 0; $i < $max; $i++)
		{
			$file = $this->GetFirst($outTotal);
			if ($total === -1)
				$total = $outTotal;
			if($file == '')
				break;
			if ($this->doProcess($file))
				$sucess++;
			else
				$failed++;
			$done++;
		}

		$lock->Release();

		return [
			'success' => $sucess,
			'done' => $done,
			'failed' => $failed,
			'total' => $total,
			'formatted' => $this->FormatResults($done, $sucess, $failed, $total),
		];
	}

	private function FormatResults(int $done, int $success, int $failed, int $total) : string
	{
		if ($total === 0)
			return 'Hecho 0 Ítems.';
		$ret = 'Hecho ' . $done . ' Ítems de ' . $total . '.';
		if ($failed > 0)
			$ret .= ' Éxito: ' . $success . ' Ítems. Fallas: ' . $failed . ' ítems.';
		return $ret;
	}

	private function GetFirst(?int &$total) : string
	{
		$lock = new QueueLock($this->path);
		try
		{
			$lock->LockWrite();
			$path = $this->GetQueuePath('queued');
			$files = IO::GetFilesFullPath($path, '.json');
			$total = count($files);
			if(isset($files[0]))
				return $this->MoveToRunning($files[0]);
			return '';
		}
		finally
		{
			$lock->Release();
		}
	}

	protected function ProcessItem(string $function, array $params)
	{
		if ($this->processorClass == '')
			throw new ErrorException('Debe indicar un ProcessorClass o implementarse un ProcessItem');

		return $this->Call($this->processorClass, $function, $params);
	}

	private function CreateFile(array $data) : void
	{
		$this->EnsureQueuePath();

		$lock = new QueueLock($this->path);

		$lock->LockWrite();

		$file = $this->GetQueuePath('queued') . '/' . microtime(true) . '.json';
		//$json = Serializator::JsonSerialize($data);
		$json = json_encode($data, JSON_INVALID_UTF8_SUBSTITUTE);
		if($json === false)
		{
			Log::HandleSilentException(new ErrorException('Error json_encode "' . json_last_error_msg() . '" en:' . "\n\n" . print_r($data, true)));
			return;
		}

		file_put_contents($file, $json);

		$lock->Release();
	}

	private function SaveException(string $file, \Exception $exception) : void
	{
		$text = Log::InternalExceptionToText($exception);

		IO::WriteAllText($file, $text);

		Log::HandleSilentException($exception);
	}
}
