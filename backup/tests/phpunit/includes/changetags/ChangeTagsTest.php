<?php

use MediaWiki\MediaWikiServices;

/**
 * @covers ChangeTags
 * @group Database
 */
class ChangeTagsTest extends MediaWikiTestCase {

	public function setUp() {
		parent::setUp();

		$this->tablesUsed[] = 'change_tag';
		$this->tablesUsed[] = 'change_tag_def';
		$this->tablesUsed[] = 'tag_summary';
		$this->tablesUsed[] = 'valid_tag';

		// Truncate these to avoid the supposed-to-be-unused IDs in tests here turning
		// out to be used, leading ChangeTags::updateTags() to pick up bogus rc_id,
		// log_id, or rev_id values and run into unique constraint violations.
		$this->tablesUsed[] = 'recentchanges';
		$this->tablesUsed[] = 'logging';
		$this->tablesUsed[] = 'revision';
		$this->tablesUsed[] = 'archive';
	}

	// TODO only modifyDisplayQuery and getSoftwareTags are tested, nothing else is

	/** @dataProvider provideModifyDisplayQuery */
	public function testModifyDisplayQuery( $origQuery, $filter_tag, $useTags, $modifiedQuery ) {
		$this->setMwGlobals( 'wgUseTagFilter', $useTags );
		// HACK resolve deferred group concats (see comment in provideModifyDisplayQuery)
		if ( isset( $modifiedQuery['fields']['ts_tags'] ) ) {
			$modifiedQuery['fields']['ts_tags'] = call_user_func_array(
				[ wfGetDB( DB_REPLICA ), 'buildGroupConcatField' ],
				$modifiedQuery['fields']['ts_tags']
			);
		}
		if ( isset( $modifiedQuery['exception'] ) ) {
			$this->setExpectedException( $modifiedQuery['exception'] );
		}
		ChangeTags::modifyDisplayQuery(
			$origQuery['tables'],
			$origQuery['fields'],
			$origQuery['conds'],
			$origQuery['join_conds'],
			$origQuery['options'],
			$filter_tag
		);
		if ( !isset( $modifiedQuery['exception'] ) ) {
			$this->assertArrayEquals(
				$modifiedQuery,
				$origQuery,
				/* ordered = */ false,
				/* named = */ true
			);
		}
	}

	public function provideModifyDisplayQuery() {
		// HACK if we call $dbr->buildGroupConcatField() now, it will return the wrong table names
		// We have to have the test runner call it instead
		$groupConcats = [
			'recentchanges' => [ ',', 'change_tag', 'ct_tag', 'ct_rc_id=rc_id' ],
			'logging' => [ ',', 'change_tag', 'ct_tag', 'ct_log_id=log_id' ],
			'revision' => [ ',', 'change_tag', 'ct_tag', 'ct_rev_id=rev_id' ],
			'archive' => [ ',', 'change_tag', 'ct_tag', 'ct_rev_id=ar_rev_id' ],
		];

		return [
			'simple recentchanges query' => [
				[
					'tables' => [ 'recentchanges' ],
					'fields' => [ 'rc_id', 'rc_timestamp' ],
					'conds' => [ "rc_timestamp > '20170714183203'" ],
					'join_conds' => [],
					'options' => [ 'ORDER BY' => 'rc_timestamp DESC' ],
				],
				'', // no tag filter
				true, // tag filtering enabled
				[
					'tables' => [ 'recentchanges' ],
					'fields' => [ 'rc_id', 'rc_timestamp', 'ts_tags' => $groupConcats['recentchanges'] ],
					'conds' => [ "rc_timestamp > '20170714183203'" ],
					'join_conds' => [],
					'options' => [ 'ORDER BY' => 'rc_timestamp DESC' ],
				]
			],
			'simple query with strings' => [
				[
					'tables' => 'recentchanges',
					'fields' => 'rc_id',
					'conds' => "rc_timestamp > '20170714183203'",
					'join_conds' => [],
					'options' => 'ORDER BY rc_timestamp DESC',
				],
				'', // no tag filter
				true, // tag filtering enabled
				[
					'tables' => [ 'recentchanges' ],
					'fields' => [ 'rc_id', 'ts_tags' => $groupConcats['recentchanges'] ],
					'conds' => [ "rc_timestamp > '20170714183203'" ],
					'join_conds' => [],
					'options' => [ 'ORDER BY rc_timestamp DESC' ],
				]
			],
			'recentchanges query with single tag filter' => [
				[
					'tables' => [ 'recentchanges' ],
					'fields' => [ 'rc_id', 'rc_timestamp' ],
					'conds' => [ "rc_timestamp > '20170714183203'" ],
					'join_conds' => [],
					'options' => [ 'ORDER BY' => 'rc_timestamp DESC' ],
				],
				'foo',
				true, // tag filtering enabled
				[
					'tables' => [ 'recentchanges', 'change_tag' ],
					'fields' => [ 'rc_id', 'rc_timestamp', 'ts_tags' => $groupConcats['recentchanges'] ],
					'conds' => [ "rc_timestamp > '20170714183203'", 'ct_tag' => 'foo' ],
					'join_conds' => [ 'change_tag' => [ 'INNER JOIN', 'ct_rc_id=rc_id' ] ],
					'options' => [ 'ORDER BY' => 'rc_timestamp DESC' ],
				]
			],
			'logging query with single tag filter and strings' => [
				[
					'tables' => 'logging',
					'fields' => 'log_id',
					'conds' => "log_timestamp > '20170714183203'",
					'join_conds' => [],
					'options' => 'ORDER BY log_timestamp DESC',
				],
				'foo',
				true, // tag filtering enabled
				[
					'tables' => [ 'logging', 'change_tag' ],
					'fields' => [ 'log_id', 'ts_tags' => $groupConcats['logging'] ],
					'conds' => [ "log_timestamp > '20170714183203'", 'ct_tag' => 'foo' ],
					'join_conds' => [ 'change_tag' => [ 'INNER JOIN', 'ct_log_id=log_id' ] ],
					'options' => [ 'ORDER BY log_timestamp DESC' ],
				]
			],
			'revision query with single tag filter' => [
				[
					'tables' => [ 'revision' ],
					'fields' => [ 'rev_id', 'rev_timestamp' ],
					'conds' => [ "rev_timestamp > '20170714183203'" ],
					'join_conds' => [],
					'options' => [ 'ORDER BY' => 'rev_timestamp DESC' ],
				],
				'foo',
				true, // tag filtering enabled
				[
					'tables' => [ 'revision', 'change_tag' ],
					'fields' => [ 'rev_id', 'rev_timestamp', 'ts_tags' => $groupConcats['revision'] ],
					'conds' => [ "rev_timestamp > '20170714183203'", 'ct_tag' => 'foo' ],
					'join_conds' => [ 'change_tag' => [ 'INNER JOIN', 'ct_rev_id=rev_id' ] ],
					'options' => [ 'ORDER BY' => 'rev_timestamp DESC' ],
				]
			],
			'archive query with single tag filter' => [
				[
					'tables' => [ 'archive' ],
					'fields' => [ 'ar_id', 'ar_timestamp' ],
					'conds' => [ "ar_timestamp > '20170714183203'" ],
					'join_conds' => [],
					'options' => [ 'ORDER BY' => 'ar_timestamp DESC' ],
				],
				'foo',
				true, // tag filtering enabled
				[
					'tables' => [ 'archive', 'change_tag' ],
					'fields' => [ 'ar_id', 'ar_timestamp', 'ts_tags' => $groupConcats['archive'] ],
					'conds' => [ "ar_timestamp > '20170714183203'", 'ct_tag' => 'foo' ],
					'join_conds' => [ 'change_tag' => [ 'INNER JOIN', 'ct_rev_id=ar_rev_id' ] ],
					'options' => [ 'ORDER BY' => 'ar_timestamp DESC' ],
				]
			],
			'unsupported table name throws exception (even without tag filter)' => [
				[
					'tables' => [ 'foobar' ],
					'fields' => [ 'fb_id', 'fb_timestamp' ],
					'conds' => [ "fb_timestamp > '20170714183203'" ],
					'join_conds' => [],
					'options' => [ 'ORDER BY' => 'fb_timestamp DESC' ],
				],
				'',
				true, // tag filtering enabled
				[ 'exception' => MWException::class ]
			],
			'tag filter ignored when tag filtering is disabled' => [
				[
					'tables' => [ 'archive' ],
					'fields' => [ 'ar_id', 'ar_timestamp' ],
					'conds' => [ "ar_timestamp > '20170714183203'" ],
					'join_conds' => [],
					'options' => [ 'ORDER BY' => 'ar_timestamp DESC' ],
				],
				'foo',
				false, // tag filtering disabled
				[
					'tables' => [ 'archive' ],
					'fields' => [ 'ar_id', 'ar_timestamp', 'ts_tags' => $groupConcats['archive'] ],
					'conds' => [ "ar_timestamp > '20170714183203'" ],
					'join_conds' => [],
					'options' => [ 'ORDER BY' => 'ar_timestamp DESC' ],
				]
			],
			'recentchanges query with multiple tag filter' => [
				[
					'tables' => [ 'recentchanges' ],
					'fields' => [ 'rc_id', 'rc_timestamp' ],
					'conds' => [ "rc_timestamp > '20170714183203'" ],
					'join_conds' => [],
					'options' => [ 'ORDER BY' => 'rc_timestamp DESC' ],
				],
				[ 'foo', 'bar' ],
				true, // tag filtering enabled
				[
					'tables' => [ 'recentchanges', 'change_tag' ],
					'fields' => [ 'rc_id', 'rc_timestamp', 'ts_tags' => $groupConcats['recentchanges'] ],
					'conds' => [ "rc_timestamp > '20170714183203'", 'ct_tag' => [ 'foo', 'bar' ] ],
					'join_conds' => [ 'change_tag' => [ 'INNER JOIN', 'ct_rc_id=rc_id' ] ],
					'options' => [ 'ORDER BY' => 'rc_timestamp DESC', 'DISTINCT' ],
				]
			],
			'recentchanges query with multiple tag filter that already has DISTINCT' => [
				[
					'tables' => [ 'recentchanges' ],
					'fields' => [ 'rc_id', 'rc_timestamp' ],
					'conds' => [ "rc_timestamp > '20170714183203'" ],
					'join_conds' => [],
					'options' => [ 'DISTINCT', 'ORDER BY' => 'rc_timestamp DESC' ],
				],
				[ 'foo', 'bar' ],
				true, // tag filtering enabled
				[
					'tables' => [ 'recentchanges', 'change_tag' ],
					'fields' => [ 'rc_id', 'rc_timestamp', 'ts_tags' => $groupConcats['recentchanges'] ],
					'conds' => [ "rc_timestamp > '20170714183203'", 'ct_tag' => [ 'foo', 'bar' ] ],
					'join_conds' => [ 'change_tag' => [ 'INNER JOIN', 'ct_rc_id=rc_id' ] ],
					'options' => [ 'DISTINCT', 'ORDER BY' => 'rc_timestamp DESC' ],
				]
			],
			'recentchanges query with multiple tag filter with strings' => [
				[
					'tables' => 'recentchanges',
					'fields' => 'rc_id',
					'conds' => "rc_timestamp > '20170714183203'",
					'join_conds' => [],
					'options' => 'ORDER BY rc_timestamp DESC',
				],
				[ 'foo', 'bar' ],
				true, // tag filtering enabled
				[
					'tables' => [ 'recentchanges', 'change_tag' ],
					'fields' => [ 'rc_id', 'ts_tags' => $groupConcats['recentchanges'] ],
					'conds' => [ "rc_timestamp > '20170714183203'", 'ct_tag' => [ 'foo', 'bar' ] ],
					'join_conds' => [ 'change_tag' => [ 'INNER JOIN', 'ct_rc_id=rc_id' ] ],
					'options' => [ 'ORDER BY rc_timestamp DESC', 'DISTINCT' ],
				]
			],
		];
	}

	public static function dataGetSoftwareTags() {
		return [
			[
				[
					'mw-contentModelChange' => true,
					'mw-redirect' => true,
					'mw-rollback' => true,
					'mw-blank' => true,
					'mw-replace' => true
				],
				[
					'mw-rollback',
					'mw-replace',
					'mw-blank'
				]
			],

			[
				[
					'mw-contentmodelchanged' => true,
					'mw-replace' => true,
					'mw-new-redirects' => true,
					'mw-changed-redirect-target' => true,
					'mw-rolback' => true,
					'mw-blanking' => false
				],
				[
					'mw-replace',
					'mw-changed-redirect-target'
				]
			],

			[
				[
					null,
					false,
					'Lorem ipsum',
					'mw-translation'
				],
				[]
			],

			[
				[],
				[]
			]
		];
	}

	/**
	 * @dataProvider dataGetSoftwareTags
	 * @covers ChangeTags::getSoftwareTags
	 */
	public function testGetSoftwareTags( $softwareTags, $expected ) {
		$this->setMwGlobals( 'wgSoftwareTags', $softwareTags );

		$actual = ChangeTags::getSoftwareTags();
		// Order of tags in arrays is not important
		sort( $expected );
		sort( $actual );
		$this->assertEquals( $expected, $actual );
	}

	public function testUpdateTagsMigrationOld() {
		$this->setMwGlobals( 'wgChangeTagsSchemaMigrationStage', MIGRATION_OLD );
		$dbw = wfGetDB( DB_MASTER );
		$dbw->delete( 'change_tag', '*' );
		$dbw->delete( 'change_tag_def', '*' );

		$rcId = 123;
		ChangeTags::updateTags( [ 'tag1', 'tag2' ], [], $rcId );

		$dbr = wfGetDB( DB_REPLICA );

		$res = $dbr->select( 'change_tag_def', [ 'ctd_name', 'ctd_id', 'ctd_count' ], '' );
		$this->assertEquals( [], iterator_to_array( $res, false ) );

		$expected2 = [
			(object)[
				'ct_tag' => 'tag1',
				'ct_tag_id' => null,
				'ct_rc_id' => 123
			],
			(object)[
				'ct_tag' => 'tag2',
				'ct_tag_id' => null,
				'ct_rc_id' => 123
			],
		];
		$res2 = $dbr->select( 'change_tag', [ 'ct_tag', 'ct_tag_id', 'ct_rc_id' ], '' );
		$this->assertEquals( $expected2, iterator_to_array( $res2, false ) );

		$rcId = 124;
		ChangeTags::updateTags( [ 'tag1', 'tag3' ], [], $rcId );

		$dbr = wfGetDB( DB_REPLICA );

		$res = $dbr->select( 'change_tag_def', [ 'ctd_name', 'ctd_id', 'ctd_count' ], '' );
		$this->assertEquals( [], iterator_to_array( $res, false ) );

		$expected2 = [
			(object)[
				'ct_tag' => 'tag1',
				'ct_tag_id' => null,
				'ct_rc_id' => 123
			],
			(object)[
				'ct_tag' => 'tag2',
				'ct_tag_id' => null,
				'ct_rc_id' => 123
			],
			(object)[
				'ct_tag' => 'tag1',
				'ct_tag_id' => null,
				'ct_rc_id' => 124
			],
			(object)[
				'ct_tag' => 'tag3',
				'ct_tag_id' => null,
				'ct_rc_id' => 124
			],
		];
		$res2 = $dbr->select( 'change_tag', [ 'ct_tag', 'ct_tag_id', 'ct_rc_id' ], '' );
		$this->assertEquals( $expected2, iterator_to_array( $res2, false ) );
	}

	public function testUpdateTagsMigrationWriteBoth() {
		// FIXME: fails under postgres
		$this->markTestSkippedIfDbType( 'postgres' );

		$this->setMwGlobals( 'wgChangeTagsSchemaMigrationStage', MIGRATION_WRITE_BOTH );
		$dbw = wfGetDB( DB_MASTER );
		$dbw->delete( 'change_tag', '*' );
		$dbw->delete( 'change_tag_def', '*' );

		$rcId = 123;
		ChangeTags::updateTags( [ 'tag1', 'tag2' ], [], $rcId );

		$dbr = wfGetDB( DB_REPLICA );

		$expected = [
			(object)[
				'ctd_name' => 'tag1',
				'ctd_id' => 1,
				'ctd_count' => 1
			],
			(object)[
				'ctd_name' => 'tag2',
				'ctd_id' => 2,
				'ctd_count' => 1
			],
		];
		$res = $dbr->select( 'change_tag_def', [ 'ctd_name', 'ctd_id', 'ctd_count' ], '' );
		$this->assertEquals( $expected, iterator_to_array( $res, false ) );

		$expected2 = [
			(object)[
				'ct_tag' => 'tag1',
				'ct_tag_id' => 1,
				'ct_rc_id' => 123
			],
			(object)[
				'ct_tag' => 'tag2',
				'ct_tag_id' => 2,
				'ct_rc_id' => 123
			],
		];
		$res2 = $dbr->select( 'change_tag', [ 'ct_tag', 'ct_tag_id', 'ct_rc_id' ], '' );
		$this->assertEquals( $expected2, iterator_to_array( $res2, false ) );

		$rcId = 124;
		ChangeTags::updateTags( [ 'tag1' ], [], $rcId );

		ChangeTags::updateTags( [ 'tag3' ], [], $rcId );

		$dbr = wfGetDB( DB_REPLICA );

		$expected = [
			(object)[
				'ctd_name' => 'tag1',
				'ctd_id' => 1,
				'ctd_count' => 2
			],
			(object)[
				'ctd_name' => 'tag2',
				'ctd_id' => 2,
				'ctd_count' => 1
			],
			(object)[
				'ctd_name' => 'tag3',
				'ctd_id' => 3,
				'ctd_count' => 1
			],
		];
		$res = $dbr->select( 'change_tag_def', [ 'ctd_name', 'ctd_id', 'ctd_count' ], '' );
		$this->assertEquals( $expected, iterator_to_array( $res, false ) );

		$expected2 = [
			(object)[
				'ct_tag' => 'tag1',
				'ct_tag_id' => 1,
				'ct_rc_id' => 123
			],
			(object)[
				'ct_tag' => 'tag2',
				'ct_tag_id' => 2,
				'ct_rc_id' => 123
			],
			(object)[
				'ct_tag' => 'tag1',
				'ct_tag_id' => 1,
				'ct_rc_id' => 124
			],
			(object)[
				'ct_tag' => 'tag3',
				'ct_tag_id' => 3,
				'ct_rc_id' => 124
			],
		];
		$res2 = $dbr->select( 'change_tag', [ 'ct_tag', 'ct_tag_id', 'ct_rc_id' ], '' );
		$this->assertEquals( $expected2, iterator_to_array( $res2, false ) );
	}

	public function testDeleteTagsMigrationOld() {
		$this->setMwGlobals( 'wgChangeTagsSchemaMigrationStage', MIGRATION_OLD );
		$dbw = wfGetDB( DB_MASTER );
		$dbw->delete( 'change_tag', '*' );
		$dbw->delete( 'change_tag_def', '*' );

		$rcId = 123;
		ChangeTags::updateTags( [ 'tag1', 'tag2' ], [], $rcId );

		ChangeTags::updateTags( [], [ 'tag2' ], $rcId );

		$dbr = wfGetDB( DB_REPLICA );

		$res = $dbr->select( 'change_tag_def', [ 'ctd_name', 'ctd_id', 'ctd_count' ], '' );
		$this->assertEquals( [], iterator_to_array( $res, false ) );

		$expected2 = [
			(object)[
				'ct_tag' => 'tag1',
				'ct_tag_id' => null,
				'ct_rc_id' => 123
			]
		];
		$res2 = $dbr->select( 'change_tag', [ 'ct_tag', 'ct_tag_id', 'ct_rc_id' ], '' );
		$this->assertEquals( $expected2, iterator_to_array( $res2, false ) );
	}

	public function testDeleteTagsMigrationWriteBoth() {
		$this->setMwGlobals( 'wgChangeTagsSchemaMigrationStage', MIGRATION_WRITE_BOTH );
		$dbw = wfGetDB( DB_MASTER );
		$dbw->delete( 'change_tag', '*' );
		$dbw->delete( 'change_tag_def', '*' );
		MediaWikiServices::getInstance()->resetServiceForTesting( 'NameTableStoreFactory' );

		$rcId = 123;
		ChangeTags::updateTags( [ 'tag1', 'tag2' ], [], $rcId );

		ChangeTags::updateTags( [], [ 'tag2' ], $rcId );

		$dbr = wfGetDB( DB_REPLICA );

		$expected = [
			(object)[
				'ctd_name' => 'tag1',
				'ctd_id' => 1,
				'ctd_count' => 1
			],
		];
		$res = $dbr->select( 'change_tag_def', [ 'ctd_name', 'ctd_id', 'ctd_count' ], '' );
		$this->assertEquals( $expected, iterator_to_array( $res, false ) );

		$expected2 = [
			(object)[
				'ct_tag' => 'tag1',
				'ct_tag_id' => 1,
				'ct_rc_id' => 123
			]
		];
		$res2 = $dbr->select( 'change_tag', [ 'ct_tag', 'ct_tag_id', 'ct_rc_id' ], '' );
		$this->assertEquals( $expected2, iterator_to_array( $res2, false ) );
	}

	public function testTagUsageStatisticsOldBackend() {
		$this->setMwGlobals( 'wgChangeTagsSchemaMigrationStage', MIGRATION_OLD );
		$this->setMwGlobals( 'wgTagStatisticsNewTable', false );

		$dbw = wfGetDB( DB_MASTER );
		$dbw->delete( 'change_tag', '*' );
		$dbw->delete( 'change_tag_def', '*' );

		$rcId = 123;
		ChangeTags::updateTags( [ 'tag1', 'tag2' ], [], $rcId );

		$rcId = 124;
		ChangeTags::updateTags( [ 'tag1' ], [], $rcId );

		$this->assertEquals( [ 'tag1' => 2, 'tag2' => 1 ], ChangeTags::tagUsageStatistics() );
	}

	public function testTagUsageStatisticsNewMigrationOldBackedn() {
		$this->setMwGlobals( 'wgChangeTagsSchemaMigrationStage', MIGRATION_WRITE_BOTH );
		$this->setMwGlobals( 'wgTagStatisticsNewTable', false );

		$dbw = wfGetDB( DB_MASTER );
		$dbw->delete( 'change_tag', '*' );
		$dbw->delete( 'change_tag_def', '*' );
		MediaWikiServices::getInstance()->resetServiceForTesting( 'NameTableStoreFactory' );

		$rcId = 123;
		ChangeTags::updateTags( [ 'tag1', 'tag2' ], [], $rcId );

		$rcId = 124;
		ChangeTags::updateTags( [ 'tag1' ], [], $rcId );

		$this->assertEquals( [ 'tag1' => 2, 'tag2' => 1 ], ChangeTags::tagUsageStatistics() );
	}

	public function testTagUsageStatisticsNewBackend() {
		$this->setMwGlobals( 'wgChangeTagsSchemaMigrationStage', MIGRATION_WRITE_BOTH );
		$this->setMwGlobals( 'wgTagStatisticsNewTable', true );

		$dbw = wfGetDB( DB_MASTER );
		$dbw->delete( 'change_tag', '*' );
		$dbw->delete( 'change_tag_def', '*' );
		MediaWikiServices::getInstance()->resetServiceForTesting( 'NameTableStoreFactory' );

		$rcId = 123;
		ChangeTags::updateTags( [ 'tag1', 'tag2' ], [], $rcId );

		$rcId = 124;
		ChangeTags::updateTags( [ 'tag1' ], [], $rcId );

		$this->assertEquals( [ 'tag1' => 2, 'tag2' => 1 ], ChangeTags::tagUsageStatistics() );
	}

	public function testListExplicitlyDefinedTagsOld() {
		$this->setMwGlobals( 'wgChangeTagsSchemaMigrationStage', MIGRATION_OLD );
		$dbw = wfGetDB( DB_MASTER );
		$dbw->delete( 'change_tag', '*' );
		$dbw->delete( 'change_tag_def', '*' );
		$dbw->delete( 'valid_tag', '*' );

		$rcId = 123;
		ChangeTags::updateTags( [ 'tag1', 'tag2' ], [], $rcId );
		ChangeTags::defineTag( 'tag2' );

		$this->assertEquals( [ 'tag2' ], ChangeTags::listExplicitlyDefinedTags() );
		$dbr = wfGetDB( DB_REPLICA );
		$res = $dbr->select( 'change_tag_def', [ 'ctd_name', 'ctd_user_defined' ], '' );
		$this->assertEquals( [], iterator_to_array( $res, false ) );

		$this->assertEquals( [ 'tag2' ], $dbr->selectFieldValues( 'valid_tag', 'vt_tag', '' ) );
	}

	public function testListExplicitlyDefinedTagsWriteBoth() {
		$this->setMwGlobals( 'wgChangeTagsSchemaMigrationStage', MIGRATION_WRITE_BOTH );
		$dbw = wfGetDB( DB_MASTER );
		$dbw->delete( 'change_tag', '*' );
		$dbw->delete( 'change_tag_def', '*' );
		$dbw->delete( 'valid_tag', '*' );

		$rcId = 123;
		ChangeTags::updateTags( [ 'tag1', 'tag2' ], [], $rcId );
		ChangeTags::defineTag( 'tag2' );

		$this->assertEquals( [ 'tag2' ], ChangeTags::listExplicitlyDefinedTags() );
		$dbr = wfGetDB( DB_REPLICA );

		$expected = [
			(object)[
				'ctd_name' => 'tag1',
				'ctd_user_defined' => 0
			],
			(object)[
				'ctd_name' => 'tag2',
				'ctd_user_defined' => 1
			],
		];
		$res = $dbr->select( 'change_tag_def', [ 'ctd_name', 'ctd_user_defined' ], '' );
		$this->assertEquals( $expected, iterator_to_array( $res, false ) );

		$this->assertEquals( [ 'tag2' ], $dbr->selectFieldValues( 'valid_tag', 'vt_tag', '' ) );
	}

	public function testListExplicitlyDefinedTagsNew() {
		$this->setMwGlobals( 'wgChangeTagsSchemaMigrationStage', MIGRATION_NEW );
		$dbw = wfGetDB( DB_MASTER );
		$dbw->delete( 'change_tag', '*' );
		$dbw->delete( 'change_tag_def', '*' );
		$dbw->delete( 'valid_tag', '*' );

		$rcId = 123;
		ChangeTags::updateTags( [ 'tag1', 'tag2' ], [], $rcId );
		ChangeTags::defineTag( 'tag2' );

		$this->assertEquals( [ 'tag2' ], ChangeTags::listExplicitlyDefinedTags() );
		$dbr = wfGetDB( DB_REPLICA );

		$expected = [
			(object)[
				'ctd_name' => 'tag1',
				'ctd_user_defined' => 0
			],
			(object)[
				'ctd_name' => 'tag2',
				'ctd_user_defined' => 1
			],
		];
		$res = $dbr->select( 'change_tag_def', [ 'ctd_name', 'ctd_user_defined' ], '' );
		$this->assertEquals( $expected, iterator_to_array( $res, false ) );

		$this->assertEquals( [], $dbr->selectFieldValues( 'valid_tag', 'vt_tag', '' ) );
	}
}