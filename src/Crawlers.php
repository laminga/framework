<?php

namespace minga\framework;

class Crawlers
{
	public static function UserAgentIsCrawler() : bool
	{
		$agent = Params::SafeServer('HTTP_USER_AGENT');
		// si no tiene user agent es un crawler
		if($agent == '')
			return true;

		foreach(self::GetCrawlers() as $bot)
		{
			if (Str::Contains($agent, $bot))
				return true;
		}
		return false;
	}

	public static function GetCrawlers() : array
	{
		return [
			// Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5X Build/MMB29P) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/103.0.5060.114 Mobile Safari/537.36 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)
			// Mozilla/5.0 (compatible; Googlebot/2.1; +https://www.google.com/bot.html)
			// Googlebot/2.1 (+http://www.google.com/bot.html)
			"google" => "Googlebot",
			// Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm) Chrome/
			"bing" => "bingbot",
			"yahoo" => "Slurp",
			"yandex" => "YandexBot",
			"baidu" => "Baiduspider",
			// LinkedInBot/1.0 (compatible; Mozilla/5.0; Apache-HttpClient +http://www.linkedin.com)
			"linkedin" => "LinkedInBot",
			// Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_1) AppleWebKit/600.2.5 (KHTML, like Gecko) Version/8.0.2 Safari/600.2.5 (Amazonbot/0.1; +https://developer.amazon.com/support/amazonbot)
			"amazon" => "Amazonbot",
			// Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; GPTBot/1.2; +https://openai.com/gptbot)
			// Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko); compatible; OAI-SearchBot/1.0; +https://openai.com/searchbot
			"openai" => "openai",
			// Mozilla/5.0 (compatible; SemrushBot/7~bl; +http://www.semrush.com/bot.html)
			"semrush" => "semrush",
			// 1scienceBot (contact: acquisition@1science.com, info: https://www.1science.com/1sciencebot/)
			"1science" => "1science",
			// Mozilla/5.0 (compatible; MetaJobBot; https://www.metajob.de/crawler)
			"metajob" => "metajob",
			// Mozilla/5.0 (compatible; Adsbot/3.1; +https://seostar.co/robot/)
			"seostar" => "seostar",
			// Mozilla/5.0 (compatible; FemtosearchBot/1.0; http://femtosearch.com)
			"femtosearch" => "femtosearch",
			// Mozilla/5.0 (Linux; Android 7.0;) AppleWebKit/537.36 (KHTML, like Gecko) Mobile Safari/537.36 (compatible; PetalBot;+https://webmaster.petalsearch.com/site/petalbot)
			"petalsearch" => "petalsearch",
			// Mozilla/5.0 (compatible; DataForSeoBot/1.0; +https://dataforseo.com/dataforseo-bot)
			"dataforseo" => "dataforseo",
			// Mozilla/5.0 (compatible; AhrefsBot/7.0; +http://ahrefs.com/robot/)
			"ahrefs" => "ahrefs",
			// Mozilla/5.0 (compatible; MJ12bot/v1.4.8; http://mj12bot.com/)
			"mj12bot" => "mj12bot",
			// Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.4 Safari/605.1.15 (Applebot/0.1; +http://www.apple.com/go/applebot)
			"applebot" => "applebot",
			// Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; ClaudeBot/1.0; +claudebot@anthropic.com)
			"claude" => "claudebot",
			// facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)
			// Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_1) AppleWebKit/601.2.4 (KHTML, like Gecko) Version/9.0.1 Safari/601.2.4 facebookexternalhit/1.1 Facebot Twitterbot/1.0
			"facebook" => "facebookexternalhit",
			// meta-externalagent/1.1 (+https://developers.facebook.com/docs/sharing/webmasters/crawler)
			"facebook-meta" => "meta-externalagent",
			// statista.com PublicationFinder-Crawler 2.0
			"statista" => "statista.com",
			// Mozilla/5.0 (compatible; proximic; +https://www.comscore.com/Web-Crawler)
			"comscore" => "comscore",
			// Scrapy/2.7.1 (+https://scrapy.org)
			"scrapy" => "scrapy",
			// Mozilla/5.0 (Linux; Android 5.0) AppleWebKit/537.36 (KHTML, like Gecko) Mobile Safari/537.36 (compatible; Bytespider; spider-feedback@bytedance.com)
			"bytedance" => "Bytespider",
			// Sogou web spider/4.0(+http://www.sogou.com/docs/help/webmasters.htm#07)
			"sogou" => "sogou",
			// Mozilla/5.0 (compatible; Yeti/1.1; +https://naver.me/spd)
			"naver" => "naver",
			// Go-http-client/1.1
			"go-http" => "Go-http-client",
			// curl/7.85.0
			"curl" => "curl",
			// python-requests/2.28.2
			// python-urllib3/1.26.12
			"python" => "python",
			// Turnitin (https://bit.ly/2UvnfoQ)
			"turnitin" => "Turnitin",
			// Conversor de pdf nuestro
			"aa" => "botAA-pdfconverter",
		];
	}
}
