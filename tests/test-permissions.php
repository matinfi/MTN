<?php
class MTN_Cafe_Booking_Permission_Test extends WP_UnitTestCase {
	public function test_team_member_is_scoped_to_assigned_cafe() {
		$user_id = self::factory()->user->create( array( 'role' => 'mtn_cafe_manager' ) );
		$first_cafe = self::factory()->post->create( array( 'post_type' => 'mtn_cafe', 'post_status' => 'publish' ) );
		$second_cafe = self::factory()->post->create( array( 'post_type' => 'mtn_cafe', 'post_status' => 'publish' ) );
		update_post_meta( $first_cafe, 'mtn_cafe_manager_ids', array( $user_id ) );
		$this->assertTrue( MTN_CB_Content::user_can_manage_cafe( $first_cafe, $user_id ) );
		$this->assertFalse( MTN_CB_Content::user_can_manage_cafe( $second_cafe, $user_id ) );
	}
}
