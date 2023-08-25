<?php get_header(); ?>

	<div class="o-box o-box--lg">
		<h1 class="u-margin-bottom-sm"><?php the_archive_title(); ?></h1>
		<?php if (term_description()) {
			echo term_description();
		} else { ?>
			<p><?php _e('Explore activities, accomodations, and great food in Peterborough & the Kawarthas.', 'pb'); ?></p>
		<?php } ?>
	</div>
	<?php $filters = array(
		'business_type',
	);
	$queried_object = get_queried_object(); ?>
	<form
		action="<?php echo esc_url(get_post_type_archive_link('place')); ?>"
		id="search-map-form"
		class="c-inline-form"
		aria-label="<?php _e('Search places', 'pb'); ?>"
	>
		<div class="c-inline-form__field">
			<label for="keyword" class="c-inline-form__label">
				<span class="u-sr-only">
					<?php _e('Search by', 'pb'); ?>
				</span>
				<?php _e('Name or keyword', 'pb'); ?>
			</label>
			<input
				type="search"
				id="keyword"
				class="c-inline-form__input"
				name="keyword"
				value=""
				placeholder="<?php esc_attr_e('e.g. craft beer', 'pb'); ?>"
				value="<?php if (isset($_GET['s'])) echo esc_attr($_GET['s']); ?>"
			/>
		</div>
		<div class="c-inline-form__field">
			<label for="address" class="c-inline-form__label">
				<span class="u-sr-only">
					<?php _e('Search by', 'pb'); ?>
				</span>
				<?php _e('Address', 'pb'); ?>
			</label>
			<input
				type="search"
				id="address"
				class="c-inline-form__input"
				name="address"
				value=""
				placeholder="<?php _e('e.g. 270 George St N, Peterborough', 'pb'); ?>"
				value="<?php if (isset($_GET['address'])) echo esc_attr($_GET['address']); ?>"
			/>
			<div class="c-inline-form__description">
				<button type="button" id="js-use-current-location" class="o-button-bare is-style-link u-flex u-align-items-center">
					<svg xmlns="http://www.w3.org/2000/svg" width="10" height="16" viewBox="0 0 10 16">
						<path d="M1,4A4,4,0,1,1,5.52,8v5.52a.51.51,0,1,1-1,0V8A4,4,0,0,1,1,4Zm2.48,8.59a.5.5,0,0,1-.4.58,5.88,5.88,0,0,0-1.65.48,1.22,1.22,0,0,0-.37.27S1,14,1,14H1a0,0,0,0,0,0,0,1.53,1.53,0,0,0,.13.16,3.07,3.07,0,0,0,.8.4A10.67,10.67,0,0,0,5,15a9.35,9.35,0,0,0,3-.43,2.79,2.79,0,0,0,.8-.4A.42.42,0,0,0,9,14a0,0,0,0,1,0,0H9s0-.05-.06-.08a2,2,0,0,0-.37-.26,5.28,5.28,0,0,0-1.65-.48.51.51,0,0,1-.43-.56A.5.5,0,0,1,7,12.16h0A6.45,6.45,0,0,1,9,12.75,1.51,1.51,0,0,1,10,14a1.34,1.34,0,0,1-.53,1,3.91,3.91,0,0,1-1.12.56A10.76,10.76,0,0,1,5,16a11,11,0,0,1-3.33-.45A3.46,3.46,0,0,1,.56,15,1.25,1.25,0,0,1,0,14a1.54,1.54,0,0,1,1-1.25,6.16,6.16,0,0,1,1.94-.59A.47.47,0,0,1,3.49,12.59Z"/>
					</svg>
					<span class="u-display-block" style="margin-left: 6px;">
						<?php _e('Use current location', 'pb'); ?>
					</span>
				</button>
			</div>
		</div>
		<?php foreach($filters as $slug) {
			$taxonomy = get_taxonomy($slug);

			$is_selected = null;

			if (array_key_exists($taxonomy->name, $_GET)) {
				$is_selected = $_GET[$taxonomy->name];
			}
			else if (isset($queried_object->taxonomy) && $queried_object->taxonomy == $taxonomy->name) {
				$is_selected = $queried_object->term_id;
			}

			if (empty($taxonomy) || is_wp_error($taxonomy)) {
				continue;
			} ?>
			<div class="c-inline-form__field c-inline-form__field--<?php echo esc_attr($taxonomy->name); ?>">
				<label for="<?php echo esc_attr($taxonomy->name); ?>" class="c-inline-form__label">
					<?php echo esc_html($taxonomy->label); ?>
				</label>
				<?php wp_dropdown_categories(array(
					'show_option_all' => sprintf(__('All %s', 'pb'), $taxonomy->label),
					'taxonomy'        => $taxonomy->name,
					'hierarchical'    => true,
					'hide_empty'      => true,
					'id'              => $taxonomy->name,
					'name'            => $taxonomy->name,
					'class'           => 'c-inline-form__input',
					'orderby'         => 'name',
					'selected'        => $is_selected,
				)); ?>
			</div>
		<?php } ?>
		<?php $taxonomy = get_taxonomy('amenity');
		if (!is_wp_error($taxonomy) && !empty($taxonomy)) {
			$terms = get_terms($taxonomy->name);

			if (!is_wp_error($terms) && !empty($terms)) { ?>
				<fieldset class="c-inline-form__field c-inline-form__field--<?php echo esc_attr($taxonomy->name); ?>">
					<legend class="c-inline-form__label">
						<?php echo esc_html($taxonomy->label); ?>
					</legend>
					<button
						class="o-button-bare c-inline-form__popover-button js-popover-trigger"
						aria-haspopup="true"
						aria-expanded="false"
						aria-controls="<?php echo esc_attr($taxonomy->name); ?>-panel"
					>
						<div id="js-amenities-placeholder" class="c-inline-form__popover-button-placeholder">
							<?php esc_html_e('None selected', 'pb'); ?>
						</div>
						<div id="js-selected-amenities" class="c-inline-form__popover-button-text" style="display: none;"></div>
						<span class="u-sr-only">
							<?php printf(esc_html__('Select %s to filter by', 'pb'), $taxonomy->label); ?>
						</span>
					</button>
					<div
						id="<?php echo esc_attr($taxonomy->name); ?>-panel"
						class="c-popover c-popover--scrolled c-popover--padding-sm"
						style="left: 10px; top: 54px; max-width: 250px;"
					>
						<div class="is-style-h5 u-no-margin-bottom">
							<?php printf(esc_html__('Select %s', 'pb'), strtolower($taxonomy->label)); ?>
						</div>
						<p class="u-text-small u-text-muted">
							<?php esc_html_e('Results will only show places that include all of the selected amenities.', 'pb'); ?>
						</p>
						<?php foreach($terms as $term) { ?>
							<div class="c-inline-form__checkbox">
								<input
									type="checkbox"
									id="<?php echo esc_attr($taxonomy->name . '-' . $term->slug); ?>"
									value="<?php echo esc_attr($term->term_id); ?>"
									name="<?php echo esc_attr($taxonomy->rest_base); ?>"
								/>
								<label for="<?php echo esc_attr($taxonomy->name . '-' . $term->slug); ?>">
									<?php echo esc_html($term->name); ?>
								</label>
							</div>
						<?php } ?>
					</div>
				</fieldset>
			<?php }
		} ?>
		<?php $taxonomy = get_taxonomy('business_badge');
		if (!is_wp_error($taxonomy) && !empty($taxonomy)) {
			$terms = get_terms($taxonomy->name);
			if (!is_wp_error($terms) && !empty($terms)) { ?>
				<fieldset class="c-inline-form__field c-inline-form__field--<?php echo esc_attr($taxonomy->name); ?>">
					<legend class="c-inline-form__label">
						<?php echo esc_html($taxonomy->label); ?>
					</legend>
					<button
						class="o-button-bare c-inline-form__popover-button js-popover-trigger"
						aria-haspopup="true"
						aria-expanded="false"
						aria-controls="<?php echo esc_attr($taxonomy->name); ?>-panel"
					>
						<div id="js-badges-placeholder" class="c-inline-form__popover-button-placeholder">
							<?php esc_html_e('None selected', 'pb'); ?>
						</div>
						<div id="js-selected-badges" class="c-inline-form__popover-button-text" style="display: none;"></div>
						<span class="u-sr-only">
							<?php printf(esc_html__('Select %s to filter by', 'pb'), $taxonomy->label); ?>
						</span>
					</button>
					<div
						id="<?php echo esc_attr($taxonomy->name); ?>-panel"
						class="c-popover c-popover--scrolled c-popover--padding-sm"
						style="left: 10px; top: 54px; max-width: 250px;"
					>
						<div class="is-style-h5 u-no-margin-bottom">
							<?php printf(esc_html__('Select %s', 'pb'), strtolower($taxonomy->label)); ?>
						</div>
						<p class="u-text-small u-text-muted">
							<?php esc_html_e('Results will only show places that include all of the selected badges.', 'pb'); ?>
						</p>
						<?php foreach($terms as $term) { ?>
							<div class="c-inline-form__checkbox">
								<input
									type="checkbox"
									id="<?php echo esc_attr($taxonomy->name . '-' . $term->slug); ?>"
									value="<?php echo esc_attr($term->term_id); ?>"
									name="<?php echo esc_attr($taxonomy->rest_base); ?>"
								/>
								<label for="<?php echo esc_attr($taxonomy->name . '-' . $term->slug); ?>">
									<?php 
										$badge_icon = get_term_meta($term->term_id, '_pb_badge_icon', true);
										if ( !empty($badge_icon) ) {	
											$badge_icon_url = wp_get_attachment_image_src($badge_icon, 'full');
											echo '<img src="' . $badge_icon_url[0] . '" alt="' . $term->name . '" height="20" width="20" style="margin-right: 5px; object-fit: contain;">';
										}
										echo esc_html($term->name); 
									?>
								</label>
							</div>
						<?php } ?>
					</div>
				</fieldset>
			<?php }
		} ?>
		<div class="c-inline-form__field c-inline-form__field--fixed c-inline-form__field--actions">
			<button type="submit" class="c-inline-form__button">
				<?php _e('Search', 'pb'); ?>
				<span class="u-sr-only"><?php _e('Businesses', 'pb'); ?></span>
			</button>
			<a href="<?php echo esc_url(get_post_type_archive_link('place')); ?>" class="c-inline-form__button is-style-reset">
				<?php _e('Reset', 'pb'); ?>
			</a>
		</div>
	</form>
	<div id="js-map-errors"></div>
	<div class="c-map__container c-map__container--split alignfull">
		<div id="js-map-list-wrapper" class="c-map__list-container" style="display: none;">
			<div class="c-map__list-title" id="js-map-list-count">0 places found</div>
			<ul id="js-map-list" class="o-list-bare c-map-list"></ul>
			<button id="js-map-list-load-more" class="c-map-list__load_button" data-page="2" style="display: none;">
				<?php _e('Load more', 'pb'); ?>
			</button>
		</div>
		<div id="places-map" class="c-map c-map--tall"></div>
	</div>

<?php get_footer();
