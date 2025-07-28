/**
 * The save function defines the way in which the different attributes should
 * be combined into the final markup, which is then serialized by the block
 * editor into `post_content`.
 *
 * Since this is a dynamic block that renders server-side, we return null
 * to indicate that the block should be rendered using the render callback.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#save
 *
 * @return {null} Null to indicate server-side rendering.
 */
export default function save() {
	// Return null to use server-side rendering via render callback
	return null;
}
