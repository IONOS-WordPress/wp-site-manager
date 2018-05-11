<?php
class LoggerTest extends WP_UnitTestCase {
	public function test() {
		require_once dirname( __FILE__ ) . '/../inc/logger.php';

		$logger = new Site_Manager_Logger();

		$readme = $logger->get_readme( dirname( __FILE__ ) . '/data/test-readme.txt' );

		$this->assertArrayHasKey( 'description', $readme );
		$this->assertArrayHasKey( 'changelog', $readme );
	}
}
