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
     * @param array $topic_icons List of topic icons to use
     * @param bool $return_data Returns the data instead of directly assigning to template
     * @return array
     */
    public function assign_icons_to_template(int $topic_id, int $icon_id, array $topic_icons = [], bool $return_data = false)
    {
        if ($topic_id && !count($topic_icons))
        {
            $sql = "SELECT icon_id FROM {$this->topic_icons_table} WHERE topic_id = $topic_id";
            $result = $this->db->sql_query($sql);
            $topic_icons = $this->db->sql_fetchrowset($result);
            $this->db->sql_freeresult($result);
        }

        $root_path = (defined('PHPBB_USE_BOARD_URL_PATH') && PHPBB_USE_BOARD_URL_PATH) ? generate_board_url() . '/' : $this->root_path;
        $icons = $this->cache->obtain_icons();
        $template_data = [];
        foreach ($icons as $id => $data)
        {
            if ($data['display'])
            {
                $topic_icon = array_filter($topic_icons, function ($icon) use ($id)
                {
                    return (isset($icon['icon_id']) ? $icon['icon_id'] : $icon) == $id;
                });
                $block_vars = [
                    'ICON_ID' => $id,
                    'ICON_IMG' => $root_path . $this->config['icons_path'] . '/' . $data['img'],
                    'ICON_WIDTH' => $data['width'],
                    'ICON_HEIGHT' => $data['height'],
                    'ICON_ALT' => $data['alt'],
                    'S_CHECKED' => !empty($topic_icon) || $id === $icon_id,
                ];
                if ($return_data)
                {
                    $template_data[] = $block_vars;
                    continue;
                }
                $this->template->assign_block_vars('topic_icons', $block_vars);
            }
        }

        return $template_data;
    }

    /**
     * Save topic icons
     *
     * @param int $topic_id The topic ID
     * @param array $topic_icons List of topic icons to save
     */
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

    /**
     * Gets the topic icons for a list of topic IDs.
     *
     * @param array $topic_list The topic list for which to retrieve the topic icons
     * @return mixed
     */
    public function get_topics_icons(array $topic_list)
    {
        $sql = "SELECT topic_id, icon_id FROM {$this->topic_icons_table}
                WHERE " . $this->db->sql_in_set('topic_id', $topic_list);
        $result = $this->db->sql_query($sql);
        $rowset = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);

        return $rowset;
    }
}
