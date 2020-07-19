<?php
/**
 *
 * Multi-Topic Icons. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2020, David Colón, https://www.davidiq.com/
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace davidiq\multitopicicons;

/**
 * Multi-Topic Icons Service info.
 */
class service
{
    /* @var \phpbb\cache\service */
    protected $cache;

    /* @var \phpbb\config\config */
    protected $config;

    /* @var \phpbb\template\template */
    protected $template;

    /* @var \phpbb\db\driver\driver_interface */
    protected $db;

    /* @var string */
    protected $root_path;

    /** @var string */
    protected $topic_icons_table;

    /**
     * Constructor
     *
     * @param \phpbb\cache\service $cache Cache object
     * @param \phpbb\config\config $config
     * @param \phpbb\template\template $template
     * @param \phpbb\db\driver\driver_interface $db
     * @param string $root_path Root path of forum
     * @param string $topic_icons_table The topic_icons table name
     */
    public function __construct(\phpbb\cache\service $cache, \phpbb\config\config $config, \phpbb\template\template $template, \phpbb\db\driver\driver_interface $db, string $root_path, string $topic_icons_table)
    {
        $this->cache = $cache;
        $this->config = $config;
        $this->template = $template;
        $this->root_path = $root_path;
        $this->topic_icons_table = $topic_icons_table;
        $this->db = $db;
    }

    /**
     * Assign icons to template
     *
     * @param int $topic_id The topic ID for which to assign icons to tempalte
     * @param int $icon_id Topic's icon_id assignment
     */
    public function assign_icons_to_template(int $topic_id, int $icon_id = 0)
    {
        $topic_icons = [];
        if ($topic_id)
        {
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
                $topic_icon = array_filter($topic_icons, function ($icon) use ($id)
                {
                    return $icon['icon_id'] == $id;
                });
                $this->template->assign_block_vars('topic_icons', [
                    'ICON_ID' => $id,
                    'ICON_IMG' => $root_path . $this->config['icons_path'] . '/' . $data['img'],
                    'ICON_WIDTH' => $data['width'],
                    'ICON_HEIGHT' => $data['height'],
                    'ICON_ALT' => $data['alt'],
                    'S_CHECKED' => !empty($topic_icon) || $id === $icon_id,
                ]);
            }
        }
    }

    public function save_topic_icons(int $topic_id, array $topic_icons)
    {
        $sql = "DELETE FROM {$this->topic_icons_table} WHERE topic_id = $topic_id";
        $this->db->sql_query($sql);

        $sql_ary = [];
        foreach ($topic_icons as $icon_id)
        {
            $sql_ary[] = [
                'topic_id' => $topic_id,
                'icon_id' => (int)$icon_id,
            ];
        }
        if (count($sql_ary))
        {
            $this->db->sql_multi_insert($this->topic_icons_table, $sql_ary);
        }
    }
}
