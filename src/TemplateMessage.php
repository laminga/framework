<?php

namespace minga\framework;

use minga\framework\enums\MailFooter;

class TemplateMessage extends AttributeEntity
{
	public string $title = '';
	public string $to = '';
	public string $toCaption = '';
	public string $formattedTo = '';
	public $footer = MailFooter::General;
	public string $template = '';
	public $content = null;
	public bool $skipNotify = false;

	public function SetTo(string $name, string $email) : void
	{
		$this->to = $email;
		$this->toCaption = $name; // mb_encode_mimeheader($name, 'UTF-8', 'Q') . ' <' . $email . '>';
	}

	public function UpdateViewActionUrl(string $url) : void
	{
		$this->attributes['viewAction']['url'] = $url;
	}

	public function AddViewAction(string $url, string $name, string $description) : void
	{
		$viewAction = [];
		$viewAction['description'] = $description;
		$viewAction['url'] = $url;
		$viewAction['name'] = $name;

		$viewAction['organization'] = Context::Settings()->applicationName;
		$viewAction['organization_url'] = Context::Settings()->GetMainServerPublicUrl();

		$this->SetValue('viewAction', $viewAction);
	}

	public function Send(string $template = '') : void
	{
		if ($template != '')
			$this->template = $template;
		$this->SetValue('title', $this->title);
		$this->SetValue('footer', $this->footer);
		if ($this->content != null)
		{
			$contentAttributes = [
				'fullName' => $this->content->GetFullName(),
				'contentTypeLabel' => $this->content->GetTypeLabel(),
				'contentTypeArticle' => $this->content->GetTypeArticle(),
				'url' => Context::Settings()->GetMainServerPublicUrl() . $this->content->publicPath,
			];

			$contentAttributes = Arr::AppendKeyArray($contentAttributes,
				$this->content->GetAllAttributes()
			);
			$this->SetValue('content', $contentAttributes);
		}
		$mail = new Mail();
		$mail->to = $this->to;
		$mail->toCaption = $this->toCaption;
		$mail->subject = $this->title;
		$mail->message = Context::Calls()->RenderMessage($this->template, $this->attributes);
		$mail->skipNotify = $this->skipNotify;
		$mail->Send();
	}

	public function SetTitle(string $title) : void
	{
		$this->SetValue('title', $title);
	}
}
