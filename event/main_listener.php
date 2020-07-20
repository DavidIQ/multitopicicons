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
            'core.posting_modify_template_vars' => 'load_topic_icons',
            'core.posting_modify_submission_errors' => 'check_max_limit',
            'core.submit_post_end' => 'save_topic_icons',
            'core.viewtopic_modify_post_row' => 'add_topic_icons',
            'core.viewforum_modify_topics_data' => 'add_topic_icons_to_topic_list_data',
            'core.viewforum_modify_topicrow' => 'add_topic_icons_to_topic_list',

            'core.acp_manage_forums_display_form' => 'acp_forum_setup',
            'core.acp_manage_forums_validate_data' => 'acp_forum_update_data',
        ];
    }

    /* @var \phpbb\language\language */
    protected $language;

    /* @var \phpbb\template\template */
    protected $template;

    /* @var \phpbb\request\request */
    protected $request;

    /* @var \davidiq\multitopicicons\service */
    protected $service;

    /**
     * Constructor
     *
     * @param \phpbb\language\language $language Language object
     * @param \phpbb\template\template $template Template object
     * @param \phpbb\request\request $request Request object
     * @param \davidiq\multitopicicons\service $service Multi topic icons service
     */
    public function __construct(\phpbb\language\language $language, \phpbb\template\template $template, \phpbb\request\request $request, \davidiq\multitopicicons\service $service)
    {
        $this->language = $language;
        $this->request = $request;
        $this->template = $template;
        $this->service = $service;
    }

    /**
     * Load multi topic icons on posting page
     *
     * @param \phpbb\event\data $event Event object
     */
    public function load_topic_icons($event)
    {
        $s_new_message = $this->template->retrieve_var('S_NEW_MESSAGE');
        $s_topic_icons = $event['s_topic_icons'];
        if ($s_topic_icons && $s_new_message)
        {
            $page_data = $event['page_data'];

            $this->language->add_lang('posting', 'davidiq/multitopicicons');
            $page_data['S_SHOW_TOPIC_ICONS'] = false;
            $page_data['S_SHOW_MULTI_TOPIC_ICONS'] = true;

            $topic_id = (int)$event['topic_id'];
            $icon_id = 0;

            if ($topic_id)
            {
                $post_data = $event['post_data'];
                $icon_id = (int)$post_data['icon_id'];
            }

            $this->service->assign_icons_to_template($topic_id, $icon_id);
            $event['page_data'] = $page_data;
        }
    }

    /**
     * ACP forum setup
     *
     * @param \phpbb\event\data $event Event object
     */
    public function acp_forum_setup($event)
    {
        $this->language->add_lang('acp', 'davidiq/multitopicicons');

        $template_data = $event['template_data'];
        $forum_data = $event['forum_data'];
        $template_data['MAX_TOPIC_ICONS'] = $forum_data['max_topic_icons'];
        $event['template_data'] = $template_data;
    }

    /**
     * Update forum data to include max topic icons
     *
     * @param $event
     */
    public function acp_forum_update_data($event)
    {
        $forum_data = $event['forum_data'];
        $forum_data['max_topic_icons'] = $this->request->variable('max_topic_icons', 0);
        $event['forum_data'] = $forum_data;
    }

    /**
     * Check that the max limit of post icons is respected
     *
     * @param \phpbb\event\data $event Event object
     */
    public function check_max_limit($event)
    {
        // Check for max allowed icons
        $post_data = $event['post_data'];
    }

    /**
     * Save checked topic icons
     *
     * @param \phpbb\event\data $event Event object
     */
    public function save_topic_icons($event)
    {
        $topic_id = (int)$event['data']['topic_id'];
        $topic_icons = $this->request->variable('topic_icons', [0]);
        $this->service->save_topic_icons($topic_id, $topic_icons);
    }

    /**
     * Add topic icons in viewtopic
     *
     * @param \phpbb\event\data $event Event object
     */
    public function add_topic_icons($event)
    {
        $post_row = $event['post_row'];
        if ($post_row['S_FIRST_POST'] && !empty($post_row['POST_ICON_IMG']))
        {
            $post_row['POST_ICON_IMG'] = false;
            $event['post_row'] = $post_row;
            $row = $event['row'];
            $topic_data = $event['topic_data'];
            $this->service->assign_icons_to_template($topic_data['topic_id'], $row['icon_id']);
        }
    }

    /**
     * Add topic icons to topics list data
     *
     * @param \phpbb\event\data $event Event object
     */
    public function add_topic_icons_to_topic_list_data($event)
    {
        $rowset = $event['rowset'];
        $topic_list = $event['topic_list'];
        $topics_icons = $this->service->get_topics_icons($topic_list);
        foreach ($topic_list as $topic_id)
        {
            $icon_id = $rowset[$topic_id]['icon_id'];
            $topic_icons = array_filter($topics_icons, function ($icon) use ($topic_id)
            {
                return $icon['topic_id'] == $topic_id;
            });
            if (!empty($topic_icons))
            {
                $topic_icons = array_map(function($icon) { return $icon['icon_id']; }, $topic_icons);
                if (!in_array($icon_id, $topic_icons))
                {
                    $topic_icons = array_merge($topic_icons, [$icon_id]);
                }
                $rowset[$topic_id]['topic_icons'] = $topic_icons;
            }
        }
        $event['rowset'] = $rowset;
    }

    /**
     * Adds topic icons to the rendered topics list
     *
     * @param $event
     */
    public function add_topic_icons_to_topic_list($event)
    {
        $row = $event['row'];
        $topic_row = $event['topic_row'];
        $topic_icons = $row['topic_icons'];
        if (!empty($topic_icons))
        {
            $topic_row['TOPIC_ICON_IMG'] = false;

            $icons_template_data = $this->service->assign_icons_to_template($row['topic_id'], $row['topic_id'], $topic_icons, true);
            $icons_template_data = array_filter($icons_template_data, function($data) { return $data['S_CHECKED']; });
            $topic_row['TOPIC_ICONS'] = $icons_template_data;

            $event['topic_row'] = $topic_row;
        }
    }
}
