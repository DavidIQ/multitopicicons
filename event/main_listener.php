<?php
/**
 *
 * Multi-Topic Icons. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2020, David Colón, https://www.davidiq.com/
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace davidiq\multitopicicons\event;

/**
 * @ignore
 */
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Multi-Topic Icons Event listener.
 */
class main_listener implements EventSubscriberInterface
{
	public static function getSubscribedEvents()
	{
		return [
			'core.posting_modify_template_vars'			=> 'load_topic_icons',
		];
	}

	/* @var \phpbb\language\language */
	protected $language;

	/**
	 * Constructor
	 *
	 * @param \phpbb\language\language	$language	Language object
	 */
	public function __construct(\phpbb\language\language $language)
	{
		$this->language = $language;
	}

	/**
	 * Load multi topic icons on posting page
	 *
	 * @param \phpbb\event\data	$event	Event object
	 */
	public function load_topic_icons($event)
	{
	    $page_data = $event['page_data'];
	    if ($page_data['S_SHOW_TOPIC_ICONS'])
        {
            $this->language->add_lang('posting', 'davidiq/multitopicicons');
            $page_data['S_SHOW_TOPIC_ICONS'] = false;
            $page_data['S_SHOW_MULTI_TOPIC_ICONS'] = true;
        }
	}
}
