<?php
/**
 *
 * Multi-Topic Icons. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2020, David Colón, https://www.davidiq.com/
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace davidiq\multitopicicons\migrations;

class install_schema extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return $this->db_tools->sql_table_exists($this->table_prefix . 'topic_icons');
	}

	public static function depends_on()
	{
		return ['\phpbb\db\migration\data\v330\v330'];
	}

	/**
	 * Adds max_topic_icons column to the forums table.
	 * Adds topic_icons table.
	 *
	 * @return array Array of schema changes
	 */
	public function update_schema()
	{
		return [
			'add_tables'		=> [
				$this->table_prefix . 'topic_icons'	=> [
					'COLUMNS'		=> [
						'topic_id'			=> ['UINT', 0],
						'icon_id'			=> ['UINT', 0],
					],
                    'KEYS'          => [
                        't_id'              => ['INDEX', 'topic_id'],
                    ],
				],
			],
			'add_columns'	=> [
				$this->table_prefix . 'forums'			=> [
					'max_topic_icons'				=> ['TINT:3', 0],
				],
			],
		];
	}

	/**
	 * Remove max_topic_icons column from forums table and drop topic_icons table.
	 *
	 * @return array Array of schema changes
	 */
	public function revert_schema()
	{
		return [
			'drop_columns'	=> [
				$this->table_prefix . 'forums'			=> [
					'max_topic_icons',
				],
			],
			'drop_tables'		=> [
				$this->table_prefix . 'topic_icons',
			],
		];
	}
}
