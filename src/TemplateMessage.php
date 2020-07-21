<?php

namespace minga\framework;

use minga\framework\enums\MailFooter;

class TemplateMessage extends AttributeEntity
{
	public $title;
	public $to;
	public $toCaption = '';
	public $formattedTo = '';
	public $footer = MailFooter::General;
	public $template;
	public $content = null;
	public $skipNotify = false;

	public function SetTo($name, $email)
	{
		$this->to = $email;
		$this->toCaption = $name; // mb_encode_mimeheader($name, 'UTF-8', 'Q') . ' <' . $email . '>';
	}

	public function UpdateViewActionUrl($url)
	{
		$this->attributes['viewAction']['url'] = $url;
	}

	public function AddViewAction($url, $name, $description)
	{
		$viewAction = [];
		$viewAction['description'] = $description;
		$viewAction['url'] = $url;
		$viewAction['name'] = $name;

		$viewAction['organization'] = Context::Settings()->applicationName;
		$viewAction['organization_url'] = Context::Settings()->GetMainServerPublicUrl();

		$this->SetValue('viewAction', $viewAction );
	}

	public function Send($template = '')
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

	public function SetTitle($title)
	{
		$this->SetValue('title', $title);
	}
}
