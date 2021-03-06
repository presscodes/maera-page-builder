<?php

class Maera_PB_Check {

	private static $instance;

	public function __construct() {

		add_action( 'post_submitbox_misc_actions', array( $this, 'builder_toggle' ) );

	}

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function builder_toggle() {

		$using_builder = get_post_meta( get_the_ID(), '_maera_pb-use-builder', true );
		?>

		<div class="misc-pub-section">
			<input type="checkbox" value="1" name="use-builder" id="use-builder"<?php checked( $using_builder, 1 ); ?> />
			&nbsp;<label for="use-builder"><?php _e( 'Use Page Builder', 'maera' ); ?></label>
		</div>
		<?php

	}

	/**
	 * Determine if the post uses the builder or not.
	 *
	 * @param  int     $post_id    The post to inspect.
	 * @return bool                True if builder is used for post; false if it is not.
	 */
	public function is_builder_active( $post_id = 0 ) {

		if ( 0 == $post_id || empty( $post_id ) ) {
			$post_id = get_the_ID();
		}

		$is_builder_active = ( 1 === (int) get_post_meta( $post_id, '_maera_pb-use-builder', true ) );
		return apply_filters( 'maera_is_builder_active', $is_builder_active, $post_id );

	}

	/**
	 * Determines if a page in the process of being saved will use the builder template.
	 *
	 * @return bool    True if the builder template will be used; false if it will not.
	 */
	public function will_be_builder_active() {

		$use_builder = isset( $_POST['use-builder'] ) ? (int) isset( $_POST['use-builder'] ) : 0;
		return apply_filters( 'maera_will_be_builder_page', ( 1 === $use_builder ), $template, $use_builder );

	}

}

function maera_pb_is_builder_active( $post_id = 0 ) {

	if ( 0 == $post_id || empty( $post_id ) ) {
		$post_id = get_the_ID();
	}

	$is_builder_active = ( 1 === (int) get_post_meta( $post_id, '_maera_pb-use-builder', true ) );
	return apply_filters( 'maera_is_builder_active', $is_builder_active, $post_id );

}
