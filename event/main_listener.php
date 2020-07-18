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
            'core.posting_modify_submission_errors'     => 'check_max_limit',
            'core.submit_post_end'                      => 'save_topic_icons',
		];
	}

	/* @var \phpbb\language\language */
	protected $language;

	/* @var \phpbb\template\template */
	protected $template;

	/* @var \phpbb\db\driver\driver_interface */
	protected $db;

	/* @var string */
	protected $topic_icons_table;

	/* @var \phpbb\request\request */
    protected $request;

    /* @var \phpbb\cache\service */
    protected $cache;

    /* @var \phpbb\config\config */
    protected $config;

    /* @var string */
    protected $root_path;

    /**
     * Constructor
     *
     * @param \phpbb\language\language $language Language object
     * @param \phpbb\template\template $template Template object
     * @param \phpbb\db\driver\driver_interface $db dbal interaction
     * @param \phpbb\request\request $request Request object
     * @param \phpbb\cache\service $cache Cache object
     * @param \phpbb\config\config $config Config object
     * @param string $root_path Root path
     * @param string $topic_icons_table Topic icons table name
     */
	public function __construct(\phpbb\language\language $language, \phpbb\template\template $template, \phpbb\db\driver\driver_interface $db, \phpbb\request\request $request, \phpbb\cache\service $cache, \phpbb\config\config $config, string $root_path, string $topic_icons_table)
	{
		$this->language = $language;
		$this->template = $template;
		$this->db = $db;
		$this->topic_icons_table = $topic_icons_table;
		$this->request = $request;
		$this->cache = $cache;
		$this->config = $config;
		$this->root_path = $root_path;
	}

	/**
	 * Load multi topic icons on posting page
	 *
	 * @param \phpbb\event\data	$event	Event object
	 */
	public function load_topic_icons($event)
	{
	    $s_topic_icons = $event['s_topic_icons'];
	    if ($s_topic_icons)
        {
    	    $page_data = $event['page_data'];

            $this->language->add_lang('posting', 'davidiq/multitopicicons');
            $page_data['S_SHOW_TOPIC_ICONS'] = false;
            $page_data['S_SHOW_MULTI_TOPIC_ICONS'] = true;

            $topic_icons = [];
            $icon_id = 0;

            $topic_id = (int) $event['topic_id'];
            if ($topic_id)
            {
                $post_data = $event['post_data'];
                $icon_id = (int) $post_data['icon_id'];

                $sql = "SELECT icon_id FROM {$this->topic_icons_table} WHERE topic_id = $topic_id";
                $result = $this->db->sql_query($sql);
                $topic_icons = $this->db->sql_fetchrowset($result);
                $this->db->sql_freeresult($result);
            }

            $root_path = (defined('PHPBB_USE_BOARD_URL_PATH') && PHPBB_USE_BOARD_URL_PATH) ? generate_board_url() . '/' : $this->root_path;
            $icons = $this->cache->obtain_icons();
            foreach ($icons as $id => $data)
            {
                if ($data['display'])
                {
                    $topic_icon = array_filter($topic_icons, function($icon) use ($id) {
                        return $icon['icon_id'] == $id;
                    });
                    $this->template->assign_block_vars('topic_icons', [
                        'ICON_ID'		=> $id,
                        'ICON_IMG'		=> $root_path . $this->config['icons_path'] . '/' . $data['img'],
                        'ICON_WIDTH'	=> $data['width'],
                        'ICON_HEIGHT'	=> $data['height'],
                        'ICON_ALT'		=> $data['alt'],
                        'S_CHECKED'		=> !empty($topic_icon) || $id === $icon_id,
                    ]);
                }
            }

            $event['page_data'] = $page_data;
        }
	}

	public function check_max_limit($event)
    {
        // Check for max allowed icons
        $post_data = $event['post_data'];
    }

    public function save_topic_icons($event)
    {
        $topic_id = (int) $event['data']['topic_id'];
        $sql = "DELETE FROM {$this->topic_icons_table} WHERE topic_id = $topic_id";
        $this->db->sql_query($sql);

        $sql_ary = [];
        $topic_icons = $this->request->variable('topic_icons', [0]);
        foreach ($topic_icons as $icon_id)
        {
            $sql_ary[] = [
				'topic_id'	=> $topic_id,
				'icon_id'	=> (int) $icon_id,
			];
        }
        if (count($sql_ary))
        {
            $this->db->sql_multi_insert($this->topic_icons_table, $sql_ary);
        }
    }
}
