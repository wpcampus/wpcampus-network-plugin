<?php

function wpcampus_get_gravity_form( $form_id ) {

	$form_url = 'https://dev5290.wpcampus.org/wp-json/gf/v2/forms/' . $form_id;

	$consumer_key    = get_option( 'wpc_gf_api_key' );
	$consumer_secret = get_option( 'wpc_gf_api_secret' );

	$auth = base64_encode( "{$consumer_key}:{$consumer_secret}" );

	$form_response = wp_safe_remote_get(
		$form_url,
		[
			'headers' => [
				'Authorization' => 'Basic ' . $auth,
			],
		]
	);

	if ( empty( $form_response ) ) {
		return new WP_Error( 'wpc_get_form', 'There was a problem getting the form.' );
	}

	$form_body = wp_remote_retrieve_body( $form_response );

	if ( empty( $form_body ) ) {
		return new WP_Error( 'wpc_get_form', 'There was a problem getting the form.' );
	}

	$form = json_decode( $form_body );

	if ( empty( $form->id ) || $form->id != $form_id ) {
		return new WP_Error( 'wpc_get_form', 'There was a problem getting the form.' );
	}

	return $form;
}

function wpc_gform_prefix() {
	return 'wpc-gform';
}

function wpc_gform_field_prefix() {
	return wpc_gform_prefix() . '__field';
}

function wpc_gform_field_type_prefix( $type ) {
	return wpc_gform_field_prefix() . "--{$type}";
}

function wpc_gform_field_id_prefix( $form_id, $field_id ) {
	return wpc_gform_field_prefix() . "-{$form_id}-{$$field_id}";
}

function is_wpcampus_gform_field_type_valid( $type ) {
	return in_array( $type, [ 'section', 'hidden', 'type', 'name' ] );
}

function get_wpcampus_gfield_input_name( $field ) {
	return 'input_' . $field->id;
}

/**
 * @TODO add value
 */
function wpcampus_print_gfield_hidden( $form_id, $field ) {
	$input_name  = get_wpcampus_gfield_input_name( $field );
	$input_id    = wpc_gform_field_id_prefix( $form_id, $field->id ) . '__input';
	$input_class = wpc_gform_field_type_prefix( 'hidden' ) . '__input';
	?>
	<input id="<?php echo $input_id; ?>" name="<?php echo esc_attr( $input_name ); ?>" type="hidden" class="<?php echo esc_attr( $input_class ); ?>>" value="">
	<?php
}

function wpcampus_print_gfield_name( $form_id, $field ) {

	// @TODO
	// aria-required="true"
	// aria-invalid="false"
	// Make sure required are setup.

	// isRequired
	// <span class="gfield_required"> * <span class="sr-only"> Required</span></span>

	$form_prefix  = wpc_gform_prefix();
	$field_prefix = wpc_gform_field_prefix();

	?>
	<fieldset class="<?php echo $form_prefix; ?>__fieldset">
		<legend class="<?php echo $field_prefix; ?>__label"><?php echo $field->label; ?></legend>
		<div class="ginput_complex ginput_container no_prefix has_first_name no_middle_name has_last_name no_suffix gf_name_has_2 ginput_container_name">
			<?php

			foreach ( $field->inputs as $input ) :
				if ( $input->isHidden ) {
					continue;
				}
				// @TODO add aria label
				// aria-label="First name"
				// aria-required="true"

				// defaultValue

				$input_id   = wpc_gform_field_id_prefix( $form_id, $field->id ) . "_{$input->id}__input";
				$input_name = get_wpcampus_gfield_input_name( $input );

				?>
				<span class="name_first">
                    <input type="text" name="<?php echo esc_attr( $input_name ); ?>" id="<?php echo $input_id; ?>" value="">
                    <label for="<?php echo $input_id; ?>"><?php echo $input->label; ?></label>
                </span>
			<?php
			endforeach;

			?>
		</div>
	</fieldset>
	<?php
}

function wpcampus_print_gfield_section( $form_id, $field ) {
	if ( empty( $field->label ) ) {
		return;
	}

	$field_type_prefix = wpc_gform_field_type_prefix( 'section' );

	if ( ! empty( $field->description ) ) {
		$desc = '<div class="' . $field_type_prefix . '__description">' . $field->description . '</div>';
	} else {
		$desc = '';
	}

	?>
	<h2 class="<?php echo $field_type_prefix; ?>__title"><?php echo $field->label; ?></h2>
	<?php

	if ( ! empty( $desc ) && empty( $field->descriptionPlacement ) ) {
		echo $desc;
	}
}

function wpcampus_print_gravity_field( $form_id, $field ) {

	if ( ! is_wpcampus_gform_field_type_valid( $field->type ) ) {
		return;
	}

	$field_id = wpc_gform_field_id_prefix( $form_id, $field->id );

	$field_class = [ wpc_gform_field_prefix(), wpc_gform_field_type_prefix( $field->type ) ];

	// @TODO possible CSS: field_sublabel_below field_description_below gfield_visibility_visible

	if ( ! empty( $field_class ) ) {
		$field_class_str = ' class="' . implode( ' ', $field_class ) . '"';
	} else {
		$field_class_str = '';
	}

	?>
	<div id="<?php echo $field_id; ?>"<?php echo $field_class_str; ?>>
		<?php

		switch ( $field->type ) {
			case 'section':
				wpcampus_print_gfield_section( $form_id, $field );
				break;
			case 'name':
				wpcampus_print_gfield_name( $form_id, $field );
				break;
			case 'hidden':
				wpcampus_print_gfield_hidden( $form_id, $field );
				break;
			default:
				break;
		}
		?>

	</div>
	<?php
}

function wpcampus_print_gravity_fields( $form_id, $fields ) {

	$fields_id = "wpc-gform__fields-{$form_id}";

	// @TODO possible CSS: top_label form_sublabel_below description_below

	?>
	<div id="<?php echo $fields_id; ?>" class="wpc-gform__fields">
		<?php

		foreach ( $fields as $field ) {
			wpcampus_print_gravity_field( $form_id, $field );
		}

		?>
	</div>
	<?php

	return;

	?>
	<ul>
		<li id="field_47_2" class="gfield gfield_contains_required field_sublabel_below field_description_below gfield_visibility_visible">
			<fieldset aria-required="true" class="gfieldset">
				<legend class="gfield_label">Email Address<span class="gfield_required"> * <span class="sr-only"> Required</span></span></legend>
				<div class="ginput_complex ginput_container ginput_container_email" id="input_47_2_container">
                                <span id="input_47_2_1_container" class="ginput_left">
                                    <input class="" type="text" name="input_2" id="input_47_2" value="rachel@wpcampus.org" aria-required="true" aria-invalid="false">
                                    <label for="input_47_2">Enter Email</label>
                                </span>
					<span id="input_47_2_2_container" class="ginput_right">
                                    <input class="" type="text" name="input_2_2" id="input_47_2_2" value="rachel@wpcampus.org" aria-required="true" aria-invalid="false">
                                    <label for="input_47_2_2">Confirm Email</label>
                                </span>
					<div class="gf_clear gf_clear_complex"></div>
				</div>
			</fieldset>
		</li>
		<li id="field_47_14" class="gfield gfield_contains_required field_sublabel_below field_description_above gfield_visibility_visible"><label class="gfield_label" for="input_47_14">Where are you traveling from?<span class="gfield_required"> * <span class="sr-only"> Required</span></span></label>
			<div class="gfield_description" id="gfield_description_47_14">Provide city/state and country (if you're outside the US).</div>
			<div class="ginput_container ginput_container_text"><input name="input_14" id="input_47_14" type="text" value="" class="medium" aria-describedby="gfield_description_47_14" aria-required="true" aria-invalid="false"></div>
		</li>
		<li id="field_47_13" class="gfield gfield_contains_required field_sublabel_below field_description_below gfield_visibility_visible">
			<fieldset aria-required="true" class="gfieldset">
				<legend class="gfield_label">Do you work at a higher ed institution?<span class="gfield_required"> * <span class="sr-only"> Required</span></span></legend>
				<div class="ginput_container ginput_container_radio">
					<ul class="gfield_radio" id="input_47_13">
						<li class="gchoice_47_13_0"><input name="input_13" type="radio" value="Yes, I work at a higher ed institution" id="choice_47_13_0"><label for="choice_47_13_0" id="label_47_13_0">Yes, I work at a higher ed institution</label></li>
						<li class="gchoice_47_13_1"><input name="input_13" type="radio" value="No, but I freelance or work for a company that supports higher ed" id="choice_47_13_1"><label for="choice_47_13_1" id="label_47_13_1">No, but I freelance or work for a company that supports higher ed</label></li>
						<li class="gchoice_47_13_2"><input name="input_13" type="radio" value="No, I work outside higher ed" id="choice_47_13_2"><label for="choice_47_13_2" id="label_47_13_2">No, I work outside higher ed</label></li>
						<li class="gchoice_47_13_3"><input name="input_13" type="radio" value="I am a higher ed student" id="choice_47_13_3"><label for="choice_47_13_3" id="label_47_13_3">I am a higher ed student</label></li>
						<li class="gchoice_47_13_4"><label id="label_47_13_4" for="choice_47_13_4" class="sr-only">Other </label><label id="label_47_13_other" for="input_47_13_other" class="sr-only">Other </label><input name="input_13" type="radio" value="gf_other_choice" id="choice_47_13_4" onfocus="jQuery(this).next('input').focus();"><input id="input_47_13_other" name="input_13_other" type="text" value="Other" aria-label="Other"
								onfocus="jQuery(this).prev(&quot;input&quot;)[0].click(); if(jQuery(this).val() == &quot;Other&quot;) { jQuery(this).val(&quot;&quot;); }"
								onblur="if(jQuery(this).val().replace(&quot; &quot;, &quot;&quot;) == &quot;&quot;) { jQuery(this).val(&quot;Other&quot;); }"></li>
					</ul>
				</div>
			</fieldset>
		</li>
		<li id="field_47_7" class="gfield field_sublabel_below field_description_below gfield_visibility_visible" style="display: none;">
			<fieldset class="gfieldset">
				<legend class="gfield_label">Have you ever worked at a higher ed institution?</legend>
				<div class="ginput_container ginput_container_checkbox">
					<ul class="gfield_checkbox" id="input_47_7">
						<li class="gchoice_47_7_1">
							<input name="input_7.1" type="checkbox" value="Yes, I have worked at a higher ed institution" id="choice_47_7_1" disabled="">
							<label for="choice_47_7_1" id="label_47_7_1">Yes, I have worked at a higher ed institution</label>
						</li>
					</ul>
				</div>
			</fieldset>
		</li>
		<li id="field_47_11" class="gfield field_sublabel_below field_description_below gfield_visibility_visible" style="display: none;"><label class="gfield_label" for="input_47_11">Where in higher education have you worked?</label>
			<div class="ginput_container ginput_container_text"><input name="input_11" id="input_47_11" type="text" value="" class="large" aria-invalid="false" disabled=""></div>
		</li>
		<li id="field_47_5" class="gfield gfield_contains_required field_sublabel_below field_description_above gfield_visibility_visible"><label class="gfield_label" for="input_47_5">Biography<span class="gfield_required"> * <span class="sr-only"> Required</span></span></label>
			<div class="gfield_description" id="gfield_description_47_5">Please tell us about yourself (in third person). If selected, this biography will be edited and used on the website.</div>
			<div class="ginput_container ginput_container_textarea">
				<div id="wp-input_47_5-wrap" class="wp-core-ui wp-editor-wrap tmce-active">
					<link rel="stylesheet" id="editor-buttons-css" href="https://wpcampus.org/wp-includes/css/editor.min.css?ver=5.3.2" type="text/css" media="all">
					<div id="wp-input_47_5-editor-container" class="wp-editor-container">
						<div id="mceu_23" class="mce-tinymce mce-container mce-panel" hidefocus="1" tabindex="-1" role="application" style="visibility: hidden; border-width: 1px; width: 100%;">
							<div id="mceu_23-body" class="mce-container-body mce-stack-layout">
								<div id="mceu_24" class="mce-top-part mce-container mce-stack-layout-item mce-first">
									<div id="mceu_24-body" class="mce-container-body">
										<div id="mceu_25" class="mce-toolbar-grp mce-container mce-panel mce-first mce-last" hidefocus="1" tabindex="-1" role="group">
											<div id="mceu_25-body" class="mce-container-body mce-stack-layout">
												<div id="mceu_26" class="mce-container mce-toolbar mce-stack-layout-item mce-first" role="toolbar">
													<div id="mceu_26-body" class="mce-container-body mce-flow-layout">
														<div id="mceu_27" class="mce-container mce-flow-layout-item mce-first mce-last mce-btn-group" role="group">
															<div id="mceu_27-body">
																<div id="mceu_0" class="mce-widget mce-btn mce-menubtn mce-fixed-width mce-listbox mce-first mce-btn-has-text" tabindex="-1" aria-labelledby="mceu_0" role="button" aria-haspopup="true">
																	<button id="mceu_0-open" role="presentation" type="button" tabindex="-1"><span class="mce-txt">Paragraph</span> <i class="mce-caret"></i></button>
																</div>
																<div id="mceu_1" class="mce-widget mce-btn" tabindex="-1" aria-pressed="false" role="button" aria-label="Bold (⌘B)">
																	<button id="mceu_1-button" role="presentation" type="button" tabindex="-1"><i class="mce-ico mce-i-bold"></i></button>
																</div>
																<div id="mceu_2" class="mce-widget mce-btn" tabindex="-1" aria-pressed="false" role="button" aria-label="Italic (⌘I)">
																	<button id="mceu_2-button" role="presentation" type="button" tabindex="-1"><i class="mce-ico mce-i-italic"></i></button>
																</div>
																<div id="mceu_3" class="mce-widget mce-btn" tabindex="-1" aria-pressed="false" role="button" aria-label="Bulleted list (⌃⌥U)">
																	<button id="mceu_3-button" role="presentation" type="button" tabindex="-1"><i class="mce-ico mce-i-bullist"></i></button>
																</div>
																<div id="mceu_4" class="mce-widget mce-btn" tabindex="-1" aria-pressed="false" role="button" aria-label="Numbered list (⌃⌥O)">
																	<button id="mceu_4-button" role="presentation" type="button" tabindex="-1"><i class="mce-ico mce-i-numlist"></i></button>
																</div>
																<div id="mceu_5" class="mce-widget mce-btn" tabindex="-1" aria-pressed="false" role="button" aria-label="Blockquote (⌃⌥Q)">
																	<button id="mceu_5-button" role="presentation" type="button" tabindex="-1"><i class="mce-ico mce-i-blockquote"></i></button>
																</div>
																<div id="mceu_6" class="mce-widget mce-btn" tabindex="-1" aria-pressed="false" role="button" aria-label="Align left (⌃⌥L)">
																	<button id="mceu_6-button" role="presentation" type="button" tabindex="-1"><i class="mce-ico mce-i-alignleft"></i></button>
																</div>
																<div id="mceu_7" class="mce-widget mce-btn" tabindex="-1" aria-pressed="false" role="button" aria-label="Align center (⌃⌥C)">
																	<button id="mceu_7-button" role="presentation" type="button" tabindex="-1"><i class="mce-ico mce-i-aligncenter"></i></button>
																</div>
																<div id="mceu_8" class="mce-widget mce-btn" tabindex="-1" aria-pressed="false" role="button" aria-label="Align right (⌃⌥R)">
																	<button id="mceu_8-button" role="presentation" type="button" tabindex="-1"><i class="mce-ico mce-i-alignright"></i></button>
																</div>
																<div id="mceu_9" class="mce-widget mce-btn" tabindex="-1" aria-pressed="false" role="button" aria-label="Insert/edit link (⌘K)">
																	<button id="mceu_9-button" role="presentation" type="button" tabindex="-1"><i class="mce-ico mce-i-link"></i></button>
																</div>
																<div id="mceu_10" class="mce-widget mce-btn" tabindex="-1" aria-pressed="false" role="button" aria-label="Fullscreen">
																	<button id="mceu_10-button" role="presentation" type="button" tabindex="-1"><i class="mce-ico mce-i-fullscreen"></i></button>
																</div>
																<div id="mceu_11" class="mce-widget mce-btn mce-last mce-active" tabindex="-1" role="button" aria-label="Toolbar Toggle (⌃⌥Z)" aria-pressed="true">
																	<button id="mceu_11-button" role="presentation" type="button" tabindex="-1"><i class="mce-ico mce-i-wp_adv"></i></button>
																</div>
															</div>
														</div>
													</div>
												</div>
												<div id="mceu_28" class="mce-container mce-toolbar mce-stack-layout-item mce-last" role="toolbar">
													<div id="mceu_28-body" class="mce-container-body mce-flow-layout">
														<div id="mceu_29" class="mce-container mce-flow-layout-item mce-first mce-last mce-btn-group" role="group">
															<div id="mceu_29-body">
																<div id="mceu_12" class="mce-widget mce-btn mce-first" tabindex="-1" aria-pressed="false" role="button" aria-label="Strikethrough (⌃⌥D)">
																	<button id="mceu_12-button" role="presentation" type="button" tabindex="-1"><i class="mce-ico mce-i-strikethrough"></i></button>
																</div>
																<div id="mceu_13" class="mce-widget mce-btn" tabindex="-1" role="button" aria-label="Horizontal line">
																	<button id="mceu_13-button" role="presentation" type="button" tabindex="-1"><i class="mce-ico mce-i-hr"></i></button>
																</div>
																<div id="mceu_14" class="mce-widget mce-btn mce-splitbtn mce-colorbutton" role="button" tabindex="-1" aria-haspopup="true" aria-label="Text color">
																	<button role="presentation" hidefocus="1" type="button" tabindex="-1"><i class="mce-ico mce-i-forecolor"></i><span id="mceu_14-preview" class="mce-preview"></span></button>
																	<button type="button" class="mce-open" hidefocus="1" tabindex="-1"><i class="mce-caret"></i></button>
																</div>
																<div id="mceu_15" class="mce-widget mce-btn" tabindex="-1" aria-pressed="false" role="button" aria-label="Paste as text">
																	<button id="mceu_15-button" role="presentation" type="button" tabindex="-1"><i class="mce-ico mce-i-pastetext"></i></button>
																</div>
																<div id="mceu_16" class="mce-widget mce-btn" tabindex="-1" role="button" aria-label="Clear formatting">
																	<button id="mceu_16-button" role="presentation" type="button" tabindex="-1"><i class="mce-ico mce-i-removeformat"></i></button>
																</div>
																<div id="mceu_17" class="mce-widget mce-btn" tabindex="-1" role="button" aria-label="Special character">
																	<button id="mceu_17-button" role="presentation" type="button" tabindex="-1"><i class="mce-ico mce-i-charmap"></i></button>
																</div>
																<div id="mceu_18" class="mce-widget mce-btn" tabindex="-1" role="button" aria-label="Decrease indent">
																	<button id="mceu_18-button" role="presentation" type="button" tabindex="-1"><i class="mce-ico mce-i-outdent"></i></button>
																</div>
																<div id="mceu_19" class="mce-widget mce-btn" tabindex="-1" role="button" aria-label="Increase indent">
																	<button id="mceu_19-button" role="presentation" type="button" tabindex="-1"><i class="mce-ico mce-i-indent"></i></button>
																</div>
																<div id="mceu_20" class="mce-widget mce-btn mce-disabled" tabindex="-1" role="button" aria-label="Undo (⌘Z)" aria-disabled="true">
																	<button id="mceu_20-button" role="presentation" type="button" tabindex="-1"><i class="mce-ico mce-i-undo"></i></button>
																</div>
																<div id="mceu_21" class="mce-widget mce-btn mce-disabled" tabindex="-1" role="button" aria-label="Redo (⌘Y)" aria-disabled="true">
																	<button id="mceu_21-button" role="presentation" type="button" tabindex="-1"><i class="mce-ico mce-i-redo"></i></button>
																</div>
																<div id="mceu_22" class="mce-widget mce-btn mce-last" tabindex="-1" role="button" aria-label="Keyboard Shortcuts (⌃⌥H)">
																	<button id="mceu_22-button" role="presentation" type="button" tabindex="-1"><i class="mce-ico mce-i-wp_help"></i></button>
																</div>
															</div>
														</div>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>
								<div id="mceu_30" class="mce-edit-area mce-container mce-panel mce-stack-layout-item" hidefocus="1" tabindex="-1" role="group" style="border-width: 1px 0px 0px;">
									<iframe id="input_47_5_ifr" allowtransparency="true" title="Rich Text Area. Press Control-Option-H for help." style="width: 100%; height: 180px; display: block;" frameborder="0"></iframe>
								</div>
								<div id="mceu_31" class="mce-statusbar mce-container mce-panel mce-stack-layout-item mce-last" hidefocus="1" tabindex="-1" role="group" style="border-width: 1px 0px 0px;">
									<div id="mceu_31-body" class="mce-container-body mce-flow-layout">
										<div id="mceu_32" class="mce-path mce-flow-layout-item mce-first">
											<div class="mce-path-item">&nbsp;</div>
										</div>
										<div id="mceu_33" class="mce-flow-layout-item mce-last mce-resizehandle"><i class="mce-ico mce-i-resize"></i></div>
									</div>
								</div>
							</div>
						</div>
						<textarea class="medium wp-editor-area" style="height: 180px; display: none;" autocomplete="off" cols="40" name="input_5" id="input_47_5" aria-hidden="true">I'm the Director/Founder of WPCampus and a freelance software engineer and accessibility/higher ed consultant. When I'm not using WordPress to help build the web, I enjoy promoting the importance of universal design and an open web. Be sure to say hi on Twitter @bamadesigner.</textarea>
						<div class="charleft ginput_counter ginput_counter_tinymce">276 of 1500 max characters</div>
					</div>
				</div>

			</div>
		</li>
		<li id="field_47_10" class="gfield field_sublabel_below field_description_below gfield_visibility_visible"><label class="gfield_label" for="input_47_10">Personal Website <span id="field_47_10_dmessage" class="sr-only"> - enter a valid website URL for example https://www.google.com</span></label>
			<div class="ginput_container ginput_container_website">
				<input aria-describedby="field_47_10_dmessage" name="input_10" id="input_47_10" type="text" value="https://bamadesigner.com/" class="medium" placeholder="http://" aria-invalid="false">
			</div>
		</li>
		<li id="field_47_4" class="gfield gf_left_half field_sublabel_below field_description_below gfield_visibility_visible"><label class="gfield_label" for="input_47_4">Twitter Username</label>
			<div class="ginput_container ginput_container_text"><input name="input_4" id="input_47_4" type="text" value="bamadesigner" class="medium" placeholder="@username" aria-invalid="false"></div>
		</li>
		<li id="field_47_22" class="gfield gf_right_half field_sublabel_below field_description_below gfield_visibility_visible"><label class="gfield_label" for="input_47_22">LinkedIn Profile <span id="field_47_22_dmessage" class="sr-only"> - enter a valid website URL for example https://www.google.com</span></label>
			<div class="ginput_container ginput_container_website">
				<input aria-describedby="field_47_22_dmessage" name="input_22" id="input_47_22" type="text" value="" class="medium" placeholder="http://" aria-invalid="false">
			</div>
		</li>
		<li id="field_47_49" class="gfield gf_left_half gfield_contains_required field_sublabel_below field_description_below gfield_visibility_visible"><label class="gfield_label" for="input_47_49">Company/Institution<span class="gfield_required"> * <span class="sr-only"> Required</span></span></label>
			<div class="ginput_container ginput_container_text"><input name="input_49" id="input_47_49" type="text" value="WPCampus" class="medium" aria-required="true" aria-invalid="false"></div>
		</li>
		<li id="field_47_50" class="gfield gf_right_half field_sublabel_below field_description_below gfield_visibility_visible"><label class="gfield_label" for="input_47_50">Company/Institution Website <span id="field_47_50_dmessage" class="sr-only"> - enter a valid website URL for example https://www.google.com</span></label>
			<div class="ginput_container ginput_container_website">
				<input aria-describedby="field_47_50_dmessage" name="input_50" id="input_47_50" type="text" value="" class="medium" placeholder="http://" aria-invalid="false">
			</div>
		</li>
		<li id="field_47_51" class="gfield gfield_contains_required field_sublabel_below field_description_below gfield_visibility_visible"><label class="gfield_label" for="input_47_51">Job Title<span class="gfield_required"> * <span class="sr-only"> Required</span></span></label>
			<div class="ginput_container ginput_container_text"><input name="input_51" id="input_47_51" type="text" value="Director" class="medium" aria-required="true" aria-invalid="false"></div>
		</li>
		<li id="field_47_32" class="gfield field_sublabel_below field_description_above gfield_visibility_visible">
			<fieldset class="gfieldset">
				<legend class="gfield_label">Will your presentation have a second speaker?</legend>
				<div class="gfield_description" id="gfield_description_47_32">We will allow for sessions to have multiple speakers but only one speaker will be comped per presentation.

					If your presentation requires more than 2 speakers, please let us know in the "More Information About Your Session" section.
				</div>
				<div class="ginput_container ginput_container_checkbox">
					<ul class="gfield_checkbox" id="input_47_32">
						<li class="gchoice_47_32_1">
							<input name="input_32.1" type="checkbox" value="Yes, there will be a second speaker" id="choice_47_32_1">
							<label for="choice_47_32_1" id="label_47_32_1">Yes, there will be a second speaker</label>
						</li>
					</ul>
				</div>
			</fieldset>
		</li>

		<li id="field_47_33" class="gfield gfield_contains_required field_sublabel_below field_description_below gfield_visibility_visible" style="display: none;">
			<fieldset aria-required="true" class="gfieldset">
				<legend class="gfield_label">Name<span class="gfield_required"> * <span class="sr-only"> Required</span></span></legend>
				<div class="ginput_complex ginput_container no_prefix has_first_name no_middle_name has_last_name no_suffix gf_name_has_2 ginput_container_name" id="input_47_33">

                            <span id="input_47_33_3_container" class="name_first">
                                                    <input type="text" name="input_33.3" id="input_47_33_3" value="" aria-label="First name" aria-required="true" aria-invalid="false" disabled="">
                                                    <label for="input_47_33_3">First</label>
                                                </span>

					<span id="input_47_33_6_container" class="name_last">
                                                    <input type="text" name="input_33.6" id="input_47_33_6" value="" aria-label="Last name" aria-required="true" aria-invalid="false" disabled="">
                                                    <label for="input_47_33_6">Last</label>
                                                </span>

				</div>
			</fieldset>
		</li>
		<li id="field_47_34" class="gfield gfield_contains_required field_sublabel_below field_description_below gfield_visibility_visible" style="display: none;">
			<fieldset aria-required="true" class="gfieldset">
				<legend class="gfield_label">Email Address<span class="gfield_required"> * <span class="sr-only"> Required</span></span></legend>
				<div class="ginput_complex ginput_container ginput_container_email" id="input_47_34_container">
                                <span id="input_47_34_1_container" class="ginput_left">
                                    <input class="" type="text" name="input_34" id="input_47_34" value="" aria-required="true" aria-invalid="false" disabled="">
                                    <label for="input_47_34">Enter Email</label>
                                </span>
					<span id="input_47_34_2_container" class="ginput_right">
                                    <input class="" type="text" name="input_34_2" id="input_47_34_2" value="" aria-required="true" aria-invalid="false" disabled="">
                                    <label for="input_47_34_2">Confirm Email</label>
                                </span>
					<div class="gf_clear gf_clear_complex"></div>
				</div>
			</fieldset>
		</li>
		<li id="field_47_35" class="gfield gfield_contains_required field_sublabel_below field_description_above gfield_visibility_visible" style="display: none;"><label class="gfield_label" for="input_47_35">Where is the second speaker traveling from?<span class="gfield_required"> * <span class="sr-only"> Required</span></span></label>
			<div class="gfield_description" id="gfield_description_47_35">Provide city/state and country (if you're outside the US).</div>
			<div class="ginput_container ginput_container_text"><input name="input_35" id="input_47_35" type="text" value="" class="medium" aria-describedby="gfield_description_47_35" aria-required="true" aria-invalid="false" disabled=""></div>
		</li>
		<li id="field_47_40" class="gfield gfield_contains_required field_sublabel_below field_description_below gfield_visibility_visible" style="display: none;">
			<fieldset aria-required="true" class="gfieldset">
				<legend class="gfield_label">Does the second speaker work at a higher ed institution?<span class="gfield_required"> * <span class="sr-only"> Required</span></span></legend>
				<div class="ginput_container ginput_container_radio">
					<ul class="gfield_radio" id="input_47_40">
						<li class="gchoice_47_40_0"><input name="input_40" type="radio" value="Yes, they work at a higher ed institution" id="choice_47_40_0" disabled=""><label for="choice_47_40_0" id="label_47_40_0">Yes, they work at a higher ed institution</label></li>
						<li class="gchoice_47_40_1"><input name="input_40" type="radio" value="No, but they freelance or work for a company that supports higher ed" id="choice_47_40_1" disabled=""><label for="choice_47_40_1" id="label_47_40_1">No, but they freelance or work for a company that supports higher ed</label></li>
						<li class="gchoice_47_40_2"><input name="input_40" type="radio" value="No, they work outside higher ed" id="choice_47_40_2" disabled=""><label for="choice_47_40_2" id="label_47_40_2">No, they work outside higher ed</label></li>
						<li class="gchoice_47_40_3"><input name="input_40" type="radio" value="They are a higher ed student" id="choice_47_40_3" disabled=""><label for="choice_47_40_3" id="label_47_40_3">They are a higher ed student</label></li>
						<li class="gchoice_47_40_4"><label id="label_47_40_4" for="choice_47_40_4" class="sr-only">Other </label><label id="label_47_40_other" for="input_47_40_other" class="sr-only">Other </label><input name="input_40" type="radio" value="gf_other_choice" id="choice_47_40_4" onfocus="jQuery(this).next('input').focus();" disabled=""><input id="input_47_40_other" name="input_40_other" type="text" value="Other" aria-label="Other"
								onfocus="jQuery(this).prev(&quot;input&quot;)[0].click(); if(jQuery(this).val() == &quot;Other&quot;) { jQuery(this).val(&quot;&quot;); }"
								onblur="if(jQuery(this).val().replace(&quot; &quot;, &quot;&quot;) == &quot;&quot;) { jQuery(this).val(&quot;Other&quot;); }"
								disabled=""></li>
					</ul>
				</div>
			</fieldset>
		</li>
		<li id="field_47_42" class="gfield field_sublabel_below field_description_below gfield_visibility_visible" style="display: none;">
			<fieldset class="gfieldset">
				<legend class="gfield_label">Have they ever worked at a higher ed institution?</legend>
				<div class="ginput_container ginput_container_checkbox">
					<ul class="gfield_checkbox" id="input_47_42">
						<li class="gchoice_47_42_1">
							<input name="input_42.1" type="checkbox" value="Yes, they have worked at a higher ed institution" id="choice_47_42_1" disabled="">
							<label for="choice_47_42_1" id="label_47_42_1">Yes, they have worked at a higher ed institution</label>
						</li>
					</ul>
				</div>
			</fieldset>
		</li>
		<li id="field_47_43" class="gfield field_sublabel_below field_description_below gfield_visibility_visible" style="display: none;"><label class="gfield_label" for="input_47_43">Where in higher education have they worked?</label>
			<div class="ginput_container ginput_container_text"><input name="input_43" id="input_47_43" type="text" value="" class="large" aria-invalid="false" disabled=""></div>
		</li>


		<li id="field_47_39" class="gfield gfield_contains_required field_sublabel_below field_description_above gfield_visibility_visible" style="display: none;"><label class="gfield_label" for="input_47_39">Biography<span class="gfield_required"> * <span class="sr-only"> Required</span></span></label>
			<div class="gfield_description" id="gfield_description_47_39">Please tell us about yourself (in third person). If selected, this biography will be edited and used on the website.</div>
			<div class="ginput_container ginput_container_textarea">
				<div id="wp-input_47_39-wrap" class="wp-core-ui wp-editor-wrap tmce-active">
					<div id="wp-input_47_39-editor-container" class="wp-editor-container">
						<div id="mceu_78" class="mce-tinymce mce-container mce-panel" hidefocus="1" tabindex="-1" role="application" style="visibility: hidden; border-width: 1px; width: 100%;">
							<div id="mceu_78-body" class="mce-container-body mce-stack-layout">
								<div id="mceu_79" class="mce-top-part mce-container mce-stack-layout-item mce-first">
									<div id="mceu_79-body" class="mce-container-body">
										<div id="mceu_80" class="mce-toolbar-grp mce-container mce-panel mce-first mce-last" hidefocus="1" tabindex="-1" role="group">
											<div id="mceu_80-body" class="mce-container-body mce-stack-layout">
												<div id="mceu_81" class="mce-container mce-toolbar mce-stack-layout-item mce-first" role="toolbar">
													<div id="mceu_81-body" class="mce-container-body mce-flow-layout">
														<div id="mceu_82" class="mce-container mce-flow-layout-item mce-first mce-last mce-btn-group" role="group">
															<div id="mceu_82-body">
																<div id="mceu_55" class="mce-widget mce-btn mce-menubtn mce-fixed-width mce-listbox mce-first mce-btn-has-text" tabindex="-1" aria-labelledby="mceu_55" role="button" aria-haspopup="true">
																	<button id="mceu_55-open" role="presentation" type="button" tabindex="-1"><span class="mce-txt">Paragraph</span> <i class="mce-caret"></i></button>
																</div>
																<div id="mceu_56" class="mce-widget mce-btn" tabindex="-1" aria-pressed="false" role="button" aria-label="Bold (⌘B)">
																	<button id="mceu_56-button" role="presentation" type="button" tabindex="-1"><i class="mce-ico mce-i-bold"></i></button>
																</div>
																<div id="mceu_57" class="mce-widget mce-btn" tabindex="-1" aria-pressed="false" role="button" aria-label="Italic (⌘I)">
																	<button id="mceu_57-button" role="presentation" type="button" tabindex="-1"><i class="mce-ico mce-i-italic"></i></button>
																</div>
																<div id="mceu_58" class="mce-widget mce-btn" tabindex="-1" aria-pressed="false" role="button" aria-label="Bulleted list (⌃⌥U)">
																	<button id="mceu_58-button" role="presentation" type="button" tabindex="-1"><i class="mce-ico mce-i-bullist"></i></button>
																</div>
																<div id="mceu_59" class="mce-widget mce-btn" tabindex="-1" aria-pressed="false" role="button" aria-label="Numbered list (⌃⌥O)">
																	<button id="mceu_59-button" role="presentation" type="button" tabindex="-1"><i class="mce-ico mce-i-numlist"></i></button>
																</div>
																<div id="mceu_60" class="mce-widget mce-btn" tabindex="-1" aria-pressed="false" role="button" aria-label="Blockquote (⌃⌥Q)">
																	<button id="mceu_60-button" role="presentation" type="button" tabindex="-1"><i class="mce-ico mce-i-blockquote"></i></button>
																</div>
																<div id="mceu_61" class="mce-widget mce-btn" tabindex="-1" aria-pressed="false" role="button" aria-label="Align left (⌃⌥L)">
																	<button id="mceu_61-button" role="presentation" type="button" tabindex="-1"><i class="mce-ico mce-i-alignleft"></i></button>
																</div>
																<div id="mceu_62" class="mce-widget mce-btn" tabindex="-1" aria-pressed="false" role="button" aria-label="Align center (⌃⌥C)">
																	<button id="mceu_62-button" role="presentation" type="button" tabindex="-1"><i class="mce-ico mce-i-aligncenter"></i></button>
																</div>
																<div id="mceu_63" class="mce-widget mce-btn" tabindex="-1" aria-pressed="false" role="button" aria-label="Align right (⌃⌥R)">
																	<button id="mceu_63-button" role="presentation" type="button" tabindex="-1"><i class="mce-ico mce-i-alignright"></i></button>
																</div>
																<div id="mceu_64" class="mce-widget mce-btn" tabindex="-1" aria-pressed="false" role="button" aria-label="Insert/edit link (⌘K)">
																	<button id="mceu_64-button" role="presentation" type="button" tabindex="-1"><i class="mce-ico mce-i-link"></i></button>
																</div>
																<div id="mceu_65" class="mce-widget mce-btn" tabindex="-1" aria-pressed="false" role="button" aria-label="Fullscreen">
																	<button id="mceu_65-button" role="presentation" type="button" tabindex="-1"><i class="mce-ico mce-i-fullscreen"></i></button>
																</div>
																<div id="mceu_66" class="mce-widget mce-btn mce-last mce-active" tabindex="-1" role="button" aria-label="Toolbar Toggle (⌃⌥Z)" aria-pressed="true">
																	<button id="mceu_66-button" role="presentation" type="button" tabindex="-1"><i class="mce-ico mce-i-wp_adv"></i></button>
																</div>
															</div>
														</div>
													</div>
												</div>
												<div id="mceu_83" class="mce-container mce-toolbar mce-stack-layout-item mce-last" role="toolbar">
													<div id="mceu_83-body" class="mce-container-body mce-flow-layout">
														<div id="mceu_84" class="mce-container mce-flow-layout-item mce-first mce-last mce-btn-group" role="group">
															<div id="mceu_84-body">
																<div id="mceu_67" class="mce-widget mce-btn mce-first" tabindex="-1" aria-pressed="false" role="button" aria-label="Strikethrough (⌃⌥D)">
																	<button id="mceu_67-button" role="presentation" type="button" tabindex="-1"><i class="mce-ico mce-i-strikethrough"></i></button>
																</div>
																<div id="mceu_68" class="mce-widget mce-btn" tabindex="-1" role="button" aria-label="Horizontal line">
																	<button id="mceu_68-button" role="presentation" type="button" tabindex="-1"><i class="mce-ico mce-i-hr"></i></button>
																</div>
																<div id="mceu_69" class="mce-widget mce-btn mce-splitbtn mce-colorbutton" role="button" tabindex="-1" aria-haspopup="true" aria-label="Text color">
																	<button role="presentation" hidefocus="1" type="button" tabindex="-1"><i class="mce-ico mce-i-forecolor"></i><span id="mceu_69-preview" class="mce-preview"></span></button>
																	<button type="button" class="mce-open" hidefocus="1" tabindex="-1"><i class="mce-caret"></i></button>
																</div>
																<div id="mceu_70" class="mce-widget mce-btn" tabindex="-1" aria-pressed="false" role="button" aria-label="Paste as text">
																	<button id="mceu_70-button" role="presentation" type="button" tabindex="-1"><i class="mce-ico mce-i-pastetext"></i></button>
																</div>
																<div id="mceu_71" class="mce-widget mce-btn" tabindex="-1" role="button" aria-label="Clear formatting">
																	<button id="mceu_71-button" role="presentation" type="button" tabindex="-1"><i class="mce-ico mce-i-removeformat"></i></button>
																</div>
																<div id="mceu_72" class="mce-widget mce-btn" tabindex="-1" role="button" aria-label="Special character">
																	<button id="mceu_72-button" role="presentation" type="button" tabindex="-1"><i class="mce-ico mce-i-charmap"></i></button>
																</div>
																<div id="mceu_73" class="mce-widget mce-btn" tabindex="-1" role="button" aria-label="Decrease indent">
																	<button id="mceu_73-button" role="presentation" type="button" tabindex="-1"><i class="mce-ico mce-i-outdent"></i></button>
																</div>
																<div id="mceu_74" class="mce-widget mce-btn" tabindex="-1" role="button" aria-label="Increase indent">
																	<button id="mceu_74-button" role="presentation" type="button" tabindex="-1"><i class="mce-ico mce-i-indent"></i></button>
																</div>
																<div id="mceu_75" class="mce-widget mce-btn mce-disabled" tabindex="-1" role="button" aria-label="Undo (⌘Z)" aria-disabled="true">
																	<button id="mceu_75-button" role="presentation" type="button" tabindex="-1"><i class="mce-ico mce-i-undo"></i></button>
																</div>
																<div id="mceu_76" class="mce-widget mce-btn mce-disabled" tabindex="-1" role="button" aria-label="Redo (⌘Y)" aria-disabled="true">
																	<button id="mceu_76-button" role="presentation" type="button" tabindex="-1"><i class="mce-ico mce-i-redo"></i></button>
																</div>
																<div id="mceu_77" class="mce-widget mce-btn mce-last" tabindex="-1" role="button" aria-label="Keyboard Shortcuts (⌃⌥H)">
																	<button id="mceu_77-button" role="presentation" type="button" tabindex="-1"><i class="mce-ico mce-i-wp_help"></i></button>
																</div>
															</div>
														</div>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>
								<div id="mceu_85" class="mce-edit-area mce-container mce-panel mce-stack-layout-item" hidefocus="1" tabindex="-1" role="group" style="border-width: 1px 0px 0px;">
									<iframe id="input_47_39_ifr" allowtransparency="true" title="Rich Text Area. Press Control-Option-H for help." style="width: 100%; height: 180px; display: block;" frameborder="0"></iframe>
								</div>
								<div id="mceu_86" class="mce-statusbar mce-container mce-panel mce-stack-layout-item mce-last" hidefocus="1" tabindex="-1" role="group" style="border-width: 1px 0px 0px;">
									<div id="mceu_86-body" class="mce-container-body mce-flow-layout">
										<div id="mceu_87" class="mce-path mce-flow-layout-item mce-first">
											<div class="mce-path-item">&nbsp;</div>
										</div>
										<div id="mceu_88" class="mce-flow-layout-item mce-last mce-resizehandle"><i class="mce-ico mce-i-resize"></i></div>
									</div>
								</div>
							</div>
						</div>
						<textarea class="medium wp-editor-area" style="height: 180px; display: none;" autocomplete="off" cols="40" name="input_39" id="input_47_39" disabled="" aria-hidden="true"></textarea>
						<div class="charleft ginput_counter ginput_counter_tinymce">0 of 1500 max characters</div>
					</div>
				</div>

			</div>
		</li>
		<li id="field_47_37" class="gfield field_sublabel_below field_description_below gfield_visibility_visible" style="display: none;"><label class="gfield_label" for="input_47_37">Personal Website <span id="field_47_37_dmessage" class="sr-only"> - enter a valid website URL for example https://www.google.com</span></label>
			<div class="ginput_container ginput_container_website">
				<input aria-describedby="field_47_37_dmessage" name="input_37" id="input_47_37" type="text" value="" class="medium" placeholder="http://" aria-invalid="false" disabled="">
			</div>
		</li>
		<li id="field_47_36" class="gfield gf_right_half field_sublabel_below field_description_below gfield_visibility_visible" style="display: none;"><label class="gfield_label" for="input_47_36">Twitter Username</label>
			<div class="ginput_container ginput_container_text"><input name="input_36" id="input_47_36" type="text" value="" class="medium" placeholder="@username" aria-invalid="false" disabled=""></div>
		</li>
		<li id="field_47_38" class="gfield gf_right_half field_sublabel_below field_description_below gfield_visibility_visible" style="display: none;"><label class="gfield_label" for="input_47_38">LinkedIn Profile <span id="field_47_38_dmessage" class="sr-only"> - enter a valid website URL for example https://www.google.com</span></label>
			<div class="ginput_container ginput_container_website">
				<input aria-describedby="field_47_38_dmessage" name="input_38" id="input_47_38" type="text" value="" class="medium" placeholder="http://" aria-invalid="false" disabled="">
			</div>
		</li>
		<li id="field_47_52" class="gfield gf_left_half gfield_contains_required field_sublabel_below field_description_below gfield_visibility_visible" style="display: none;"><label class="gfield_label" for="input_47_52">Company/Institution<span class="gfield_required"> * <span class="sr-only"> Required</span></span></label>
			<div class="ginput_container ginput_container_text"><input name="input_52" id="input_47_52" type="text" value="" class="medium" aria-required="true" aria-invalid="false" disabled=""></div>
		</li>
		<li id="field_47_53" class="gfield gf_right_half field_sublabel_below field_description_below gfield_visibility_visible" style="display: none;"><label class="gfield_label" for="input_47_53">Company/Institution Website <span id="field_47_53_dmessage" class="sr-only"> - enter a valid website URL for example https://www.google.com</span></label>
			<div class="ginput_container ginput_container_website">
				<input aria-describedby="field_47_53_dmessage" name="input_53" id="input_47_53" type="text" value="" class="medium" placeholder="http://" aria-invalid="false" disabled="">
			</div>
		</li>
		<li id="field_47_54" class="gfield gfield_contains_required field_sublabel_below field_description_below gfield_visibility_visible" style="display: none;"><label class="gfield_label" for="input_47_54">Job Title<span class="gfield_required"> * <span class="sr-only"> Required</span></span></label>
			<div class="ginput_container ginput_container_text"><input name="input_54" id="input_47_54" type="text" value="" class="medium" aria-required="true" aria-invalid="false" disabled=""></div>
		</li>

		<li id="field_47_46" class="gfield gfield_contains_required field_sublabel_below field_description_below gfield_visibility_visible"><label class="gfield_label" for="input_47_46">Title of Your Session<span class="gfield_required"> * <span class="sr-only"> Required</span></span></label>
			<div class="ginput_container ginput_container_post_title">
				<input name="input_46" id="input_47_46" type="text" value="" class="large" aria-required="true" aria-invalid="false">
			</div>
		</li>
		<li id="field_47_47" class="gfield gfield_contains_required field_sublabel_below field_description_above gfield_visibility_visible"><label class="gfield_label" for="input_47_47">Session Description<span class="gfield_required"> * <span class="sr-only"> Required</span></span></label>
			<div class="gfield_description" id="gfield_description_47_47">This description will be edited and used on the website. Please include 1-2 paragraphs and a list of key takeaways for the audience.</div>
			<div class="ginput_container ginput_container_textarea">
				<div id="wp-input_47_47-wrap" class="wp-core-ui wp-editor-wrap tmce-active">
					<div id="wp-input_47_47-editor-container" class="wp-editor-container">
						<div id="mceu_133" class="mce-tinymce mce-container mce-panel" hidefocus="1" tabindex="-1" role="application" style="visibility: hidden; border-width: 1px; width: 100%;">
							<div id="mceu_133-body" class="mce-container-body mce-stack-layout">
								<div id="mceu_134" class="mce-top-part mce-container mce-stack-layout-item mce-first">
									<div id="mceu_134-body" class="mce-container-body">
										<div id="mceu_135" class="mce-toolbar-grp mce-container mce-panel mce-first mce-last" hidefocus="1" tabindex="-1" role="group">
											<div id="mceu_135-body" class="mce-container-body mce-stack-layout">
												<div id="mceu_136" class="mce-container mce-toolbar mce-stack-layout-item mce-first" role="toolbar">
													<div id="mceu_136-body" class="mce-container-body mce-flow-layout">
														<div id="mceu_137" class="mce-container mce-flow-layout-item mce-first mce-last mce-btn-group" role="group">
															<div id="mceu_137-body">
																<div id="mceu_110" class="mce-widget mce-btn mce-menubtn mce-fixed-width mce-listbox mce-first mce-btn-has-text" tabindex="-1" aria-labelledby="mceu_110" role="button" aria-haspopup="true">
																	<button id="mceu_110-open" role="presentation" type="button" tabindex="-1"><span class="mce-txt">Paragraph</span> <i class="mce-caret"></i></button>
																</div>
																<div id="mceu_111" class="mce-widget mce-btn" tabindex="-1" aria-pressed="false" role="button" aria-label="Bold (⌘B)">
																	<button id="mceu_111-button" role="presentation" type="button" tabindex="-1"><i class="mce-ico mce-i-bold"></i></button>
																</div>
																<div id="mceu_112" class="mce-widget mce-btn" tabindex="-1" aria-pressed="false" role="button" aria-label="Italic (⌘I)">
																	<button id="mceu_112-button" role="presentation" type="button" tabindex="-1"><i class="mce-ico mce-i-italic"></i></button>
																</div>
																<div id="mceu_113" class="mce-widget mce-btn" tabindex="-1" aria-pressed="false" role="button" aria-label="Bulleted list (⌃⌥U)">
																	<button id="mceu_113-button" role="presentation" type="button" tabindex="-1"><i class="mce-ico mce-i-bullist"></i></button>
																</div>
																<div id="mceu_114" class="mce-widget mce-btn" tabindex="-1" aria-pressed="false" role="button" aria-label="Numbered list (⌃⌥O)">
																	<button id="mceu_114-button" role="presentation" type="button" tabindex="-1"><i class="mce-ico mce-i-numlist"></i></button>
																</div>
																<div id="mceu_115" class="mce-widget mce-btn" tabindex="-1" aria-pressed="false" role="button" aria-label="Blockquote (⌃⌥Q)">
																	<button id="mceu_115-button" role="presentation" type="button" tabindex="-1"><i class="mce-ico mce-i-blockquote"></i></button>
																</div>
																<div id="mceu_116" class="mce-widget mce-btn" tabindex="-1" aria-pressed="false" role="button" aria-label="Align left (⌃⌥L)">
																	<button id="mceu_116-button" role="presentation" type="button" tabindex="-1"><i class="mce-ico mce-i-alignleft"></i></button>
																</div>
																<div id="mceu_117" class="mce-widget mce-btn" tabindex="-1" aria-pressed="false" role="button" aria-label="Align center (⌃⌥C)">
																	<button id="mceu_117-button" role="presentation" type="button" tabindex="-1"><i class="mce-ico mce-i-aligncenter"></i></button>
																</div>
																<div id="mceu_118" class="mce-widget mce-btn" tabindex="-1" aria-pressed="false" role="button" aria-label="Align right (⌃⌥R)">
																	<button id="mceu_118-button" role="presentation" type="button" tabindex="-1"><i class="mce-ico mce-i-alignright"></i></button>
																</div>
																<div id="mceu_119" class="mce-widget mce-btn" tabindex="-1" aria-pressed="false" role="button" aria-label="Insert/edit link (⌘K)">
																	<button id="mceu_119-button" role="presentation" type="button" tabindex="-1"><i class="mce-ico mce-i-link"></i></button>
																</div>
																<div id="mceu_120" class="mce-widget mce-btn" tabindex="-1" aria-pressed="false" role="button" aria-label="Fullscreen">
																	<button id="mceu_120-button" role="presentation" type="button" tabindex="-1"><i class="mce-ico mce-i-fullscreen"></i></button>
																</div>
																<div id="mceu_121" class="mce-widget mce-btn mce-last mce-active" tabindex="-1" role="button" aria-label="Toolbar Toggle (⌃⌥Z)" aria-pressed="true">
																	<button id="mceu_121-button" role="presentation" type="button" tabindex="-1"><i class="mce-ico mce-i-wp_adv"></i></button>
																</div>
															</div>
														</div>
													</div>
												</div>
												<div id="mceu_138" class="mce-container mce-toolbar mce-stack-layout-item mce-last" role="toolbar">
													<div id="mceu_138-body" class="mce-container-body mce-flow-layout">
														<div id="mceu_139" class="mce-container mce-flow-layout-item mce-first mce-last mce-btn-group" role="group">
															<div id="mceu_139-body">
																<div id="mceu_122" class="mce-widget mce-btn mce-first" tabindex="-1" aria-pressed="false" role="button" aria-label="Strikethrough (⌃⌥D)">
																	<button id="mceu_122-button" role="presentation" type="button" tabindex="-1"><i class="mce-ico mce-i-strikethrough"></i></button>
																</div>
																<div id="mceu_123" class="mce-widget mce-btn" tabindex="-1" role="button" aria-label="Horizontal line">
																	<button id="mceu_123-button" role="presentation" type="button" tabindex="-1"><i class="mce-ico mce-i-hr"></i></button>
																</div>
																<div id="mceu_124" class="mce-widget mce-btn mce-splitbtn mce-colorbutton" role="button" tabindex="-1" aria-haspopup="true" aria-label="Text color">
																	<button role="presentation" hidefocus="1" type="button" tabindex="-1"><i class="mce-ico mce-i-forecolor"></i><span id="mceu_124-preview" class="mce-preview"></span></button>
																	<button type="button" class="mce-open" hidefocus="1" tabindex="-1"><i class="mce-caret"></i></button>
																</div>
																<div id="mceu_125" class="mce-widget mce-btn" tabindex="-1" aria-pressed="false" role="button" aria-label="Paste as text">
																	<button id="mceu_125-button" role="presentation" type="button" tabindex="-1"><i class="mce-ico mce-i-pastetext"></i></button>
																</div>
																<div id="mceu_126" class="mce-widget mce-btn" tabindex="-1" role="button" aria-label="Clear formatting">
																	<button id="mceu_126-button" role="presentation" type="button" tabindex="-1"><i class="mce-ico mce-i-removeformat"></i></button>
																</div>
																<div id="mceu_127" class="mce-widget mce-btn" tabindex="-1" role="button" aria-label="Special character">
																	<button id="mceu_127-button" role="presentation" type="button" tabindex="-1"><i class="mce-ico mce-i-charmap"></i></button>
																</div>
																<div id="mceu_128" class="mce-widget mce-btn" tabindex="-1" role="button" aria-label="Decrease indent">
																	<button id="mceu_128-button" role="presentation" type="button" tabindex="-1"><i class="mce-ico mce-i-outdent"></i></button>
																</div>
																<div id="mceu_129" class="mce-widget mce-btn" tabindex="-1" role="button" aria-label="Increase indent">
																	<button id="mceu_129-button" role="presentation" type="button" tabindex="-1"><i class="mce-ico mce-i-indent"></i></button>
																</div>
																<div id="mceu_130" class="mce-widget mce-btn mce-disabled" tabindex="-1" role="button" aria-label="Undo (⌘Z)" aria-disabled="true">
																	<button id="mceu_130-button" role="presentation" type="button" tabindex="-1"><i class="mce-ico mce-i-undo"></i></button>
																</div>
																<div id="mceu_131" class="mce-widget mce-btn mce-disabled" tabindex="-1" role="button" aria-label="Redo (⌘Y)" aria-disabled="true">
																	<button id="mceu_131-button" role="presentation" type="button" tabindex="-1"><i class="mce-ico mce-i-redo"></i></button>
																</div>
																<div id="mceu_132" class="mce-widget mce-btn mce-last" tabindex="-1" role="button" aria-label="Keyboard Shortcuts (⌃⌥H)">
																	<button id="mceu_132-button" role="presentation" type="button" tabindex="-1"><i class="mce-ico mce-i-wp_help"></i></button>
																</div>
															</div>
														</div>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>
								<div id="mceu_140" class="mce-edit-area mce-container mce-panel mce-stack-layout-item" hidefocus="1" tabindex="-1" role="group" style="border-width: 1px 0px 0px;">
									<iframe id="input_47_47_ifr" allowtransparency="true" title="Rich Text Area. Press Control-Option-H for help." style="width: 100%; height: 180px; display: block;" frameborder="0"></iframe>
								</div>
								<div id="mceu_141" class="mce-statusbar mce-container mce-panel mce-stack-layout-item mce-last" hidefocus="1" tabindex="-1" role="group" style="border-width: 1px 0px 0px;">
									<div id="mceu_141-body" class="mce-container-body mce-flow-layout">
										<div id="mceu_142" class="mce-path mce-flow-layout-item mce-first">
											<div class="mce-path-item">&nbsp;</div>
										</div>
										<div id="mceu_143" class="mce-flow-layout-item mce-last mce-resizehandle"><i class="mce-ico mce-i-resize"></i></div>
									</div>
								</div>
							</div>
						</div>
						<textarea class="medium wp-editor-area" style="height: 180px; display: none;" autocomplete="off" cols="40" name="input_47" id="input_47_47" aria-hidden="true"></textarea>
						<div class="charleft ginput_counter ginput_counter_tinymce">0 of 1500 max characters</div>
					</div>
				</div>

			</div>
		</li>
		<li id="field_47_20" class="gfield gfield_contains_required field_sublabel_below field_description_above gfield_visibility_visible"><label class="gfield_label" for="input_47_20">More Information About Your Session<span class="gfield_required"> * <span class="sr-only"> Required</span></span></label>
			<div class="gfield_description" id="gfield_description_47_20">Please describe your session in greater detail for the organizers. You may be more casual in this description as it will not be posted on the website.</div>
			<div class="ginput_container ginput_container_textarea"><textarea name="input_20" id="input_47_20" class="textarea medium" aria-describedby="gfield_description_47_20" maxlength="1500" aria-required="true" aria-invalid="false" rows="10" cols="50"></textarea>
				<div class="charleft ginput_counter">0 of 1500 max characters</div>
			</div>
		</li>
		<li id="field_47_16" class="gfield gfield_contains_required field_sublabel_below field_description_above gfield_visibility_visible"><label class="gfield_label" for="input_47_16">Who do you feel is the best audience for your topic?<span class="gfield_required"> * <span class="sr-only"> Required</span></span></label>
			<div class="gfield_description" id="gfield_description_47_16">Who (types of people in higher ed) do you think would most likely benefit from hearing your talk?</div>
			<div class="ginput_container ginput_container_text"><input name="input_16" id="input_47_16" type="text" value="" class="large" aria-describedby="gfield_description_47_16" aria-required="true" aria-invalid="false"></div>
		</li>
		<li id="field_47_57" class="gfield gf_list_3col field_sublabel_below field_description_above gfield_visibility_visible">
			<fieldset class="gfieldset">
				<legend class="gfield_label">Session Categories</legend>
				<div class="gfield_description" id="gfield_description_47_57">Please select up to 3 categories which best describe your session. If you would like to recommend new categories, you may do so in the next field.</div>
				<div class="ginput_container ginput_container_checkbox">
					<ul class="gfield_checkbox" id="input_47_57">
						<li class="gchoice_47_57_1">
							<input name="input_57.1" type="checkbox" value="9" id="choice_47_57_1">
							<label for="choice_47_57_1" id="label_47_57_1">Accessibility</label>
						</li>
						<li class="gchoice_47_57_2">
							<input name="input_57.2" type="checkbox" value="145" id="choice_47_57_2">
							<label for="choice_47_57_2" id="label_47_57_2">Agile</label>
						</li>
						<li class="gchoice_47_57_3">
							<input name="input_57.3" type="checkbox" value="128" id="choice_47_57_3">
							<label for="choice_47_57_3" id="label_47_57_3">Analytics</label>
						</li>
						<li class="gchoice_47_57_4">
							<input name="input_57.4" type="checkbox" value="34" id="choice_47_57_4">
							<label for="choice_47_57_4" id="label_47_57_4">APIs</label>
						</li>
						<li class="gchoice_47_57_5">
							<input name="input_57.5" type="checkbox" value="225" id="choice_47_57_5">
							<label for="choice_47_57_5" id="label_47_57_5">Application Security</label>
						</li>
						<li class="gchoice_47_57_6">
							<input name="input_57.6" type="checkbox" value="162" id="choice_47_57_6">
							<label for="choice_47_57_6" id="label_47_57_6">Automation</label>
						</li>
						<li class="gchoice_47_57_7">
							<input name="input_57.7" type="checkbox" value="17" id="choice_47_57_7">
							<label for="choice_47_57_7" id="label_47_57_7">Back-end development</label>
						</li>
						<li class="gchoice_47_57_8">
							<input name="input_57.8" type="checkbox" value="166" id="choice_47_57_8">
							<label for="choice_47_57_8" id="label_47_57_8">Being human</label>
						</li>
						<li class="gchoice_47_57_9">
							<input name="input_57.9" type="checkbox" value="37" id="choice_47_57_9">
							<label for="choice_47_57_9" id="label_47_57_9">BuddyPress</label>
						</li>
						<li class="gchoice_47_57_11">
							<input name="input_57.11" type="checkbox" value="139" id="choice_47_57_11">
							<label for="choice_47_57_11" id="label_47_57_11">Change management</label>
						</li>
						<li class="gchoice_47_57_12">
							<input name="input_57.12" type="checkbox" value="161" id="choice_47_57_12">
							<label for="choice_47_57_12" id="label_47_57_12">Cloud</label>
						</li>
						<li class="gchoice_47_57_13">
							<input name="input_57.13" type="checkbox" value="157" id="choice_47_57_13">
							<label for="choice_47_57_13" id="label_47_57_13">Collaboration with faculty</label>
						</li>
						<li class="gchoice_47_57_14">
							<input name="input_57.14" type="checkbox" value="140" id="choice_47_57_14">
							<label for="choice_47_57_14" id="label_47_57_14">Communication</label>
						</li>
						<li class="gchoice_47_57_15">
							<input name="input_57.15" type="checkbox" value="211" id="choice_47_57_15">
							<label for="choice_47_57_15" id="label_47_57_15">Community</label>
						</li>
						<li class="gchoice_47_57_16">
							<input name="input_57.16" type="checkbox" value="26" id="choice_47_57_16">
							<label for="choice_47_57_16" id="label_47_57_16">Content</label>
						</li>
						<li class="gchoice_47_57_17">
							<input name="input_57.17" type="checkbox" value="148" id="choice_47_57_17">
							<label for="choice_47_57_17" id="label_47_57_17">Content design</label>
						</li>
						<li class="gchoice_47_57_18">
							<input name="input_57.18" type="checkbox" value="165" id="choice_47_57_18">
							<label for="choice_47_57_18" id="label_47_57_18">Content strategy</label>
						</li>
						<li class="gchoice_47_57_19">
							<input name="input_57.19" type="checkbox" value="149" id="choice_47_57_19">
							<label for="choice_47_57_19" id="label_47_57_19">Continuous deployment</label>
						</li>
						<li class="gchoice_47_57_21">
							<input name="input_57.21" type="checkbox" value="226" id="choice_47_57_21">
							<label for="choice_47_57_21" id="label_47_57_21">Continuous improvement</label>
						</li>
						<li class="gchoice_47_57_22">
							<input name="input_57.22" type="checkbox" value="150" id="choice_47_57_22">
							<label for="choice_47_57_22" id="label_47_57_22">Continuous integration</label>
						</li>
						<li class="gchoice_47_57_23">
							<input name="input_57.23" type="checkbox" value="30" id="choice_47_57_23">
							<label for="choice_47_57_23" id="label_47_57_23">Curriculum</label>
						</li>
						<li class="gchoice_47_57_24">
							<input name="input_57.24" type="checkbox" value="32" id="choice_47_57_24">
							<label for="choice_47_57_24" id="label_47_57_24">Deployment</label>
						</li>
						<li class="gchoice_47_57_25">
							<input name="input_57.25" type="checkbox" value="110" id="choice_47_57_25">
							<label for="choice_47_57_25" id="label_47_57_25">Design</label>
						</li>
						<li class="gchoice_47_57_26">
							<input name="input_57.26" type="checkbox" value="106" id="choice_47_57_26">
							<label for="choice_47_57_26" id="label_47_57_26">Development</label>
						</li>
						<li class="gchoice_47_57_27">
							<input name="input_57.27" type="checkbox" value="163" id="choice_47_57_27">
							<label for="choice_47_57_27" id="label_47_57_27">DevOps</label>
						</li>
						<li class="gchoice_47_57_28">
							<input name="input_57.28" type="checkbox" value="153" id="choice_47_57_28">
							<label for="choice_47_57_28" id="label_47_57_28">Diversity</label>
						</li>
						<li class="gchoice_47_57_29">
							<input name="input_57.29" type="checkbox" value="154" id="choice_47_57_29">
							<label for="choice_47_57_29" id="label_47_57_29">Domain of One's Own</label>
						</li>
						<li class="gchoice_47_57_31">
							<input name="input_57.31" type="checkbox" value="230" id="choice_47_57_31">
							<label for="choice_47_57_31" id="label_47_57_31">Ethics</label>
						</li>
						<li class="gchoice_47_57_32">
							<input name="input_57.32" type="checkbox" value="108" id="choice_47_57_32">
							<label for="choice_47_57_32" id="label_47_57_32">Frameworks</label>
						</li>
						<li class="gchoice_47_57_33">
							<input name="input_57.33" type="checkbox" value="10" id="choice_47_57_33">
							<label for="choice_47_57_33" id="label_47_57_33">Front-end design</label>
						</li>
						<li class="gchoice_47_57_34">
							<input name="input_57.34" type="checkbox" value="16" id="choice_47_57_34">
							<label for="choice_47_57_34" id="label_47_57_34">Front-end development</label>
						</li>
						<li class="gchoice_47_57_35">
							<input name="input_57.35" type="checkbox" value="29" id="choice_47_57_35">
							<label for="choice_47_57_35" id="label_47_57_35">Governance</label>
						</li>
						<li class="gchoice_47_57_36">
							<input name="input_57.36" type="checkbox" value="138" id="choice_47_57_36">
							<label for="choice_47_57_36" id="label_47_57_36">Gutenberg</label>
						</li>
						<li class="gchoice_47_57_37">
							<input name="input_57.37" type="checkbox" value="129" id="choice_47_57_37">
							<label for="choice_47_57_37" id="label_47_57_37">HighEdWeb Member</label>
						</li>
						<li class="gchoice_47_57_38">
							<input name="input_57.38" type="checkbox" value="242" id="choice_47_57_38">
							<label for="choice_47_57_38" id="label_47_57_38">HighEdWeb Partner Session</label>
						</li>
						<li class="gchoice_47_57_39">
							<input name="input_57.39" type="checkbox" value="231" id="choice_47_57_39">
							<label for="choice_47_57_39" id="label_47_57_39">IndieWeb</label>
						</li>
						<li class="gchoice_47_57_41">
							<input name="input_57.41" type="checkbox" value="202" id="choice_47_57_41">
							<label for="choice_47_57_41" id="label_47_57_41">Industry partnership</label>
						</li>
						<li class="gchoice_47_57_42">
							<input name="input_57.42" type="checkbox" value="197" id="choice_47_57_42">
							<label for="choice_47_57_42" id="label_47_57_42">Instructional Design</label>
						</li>
						<li class="gchoice_47_57_43">
							<input name="input_57.43" type="checkbox" value="136" id="choice_47_57_43">
							<label for="choice_47_57_43" id="label_47_57_43">Integrations</label>
						</li>
						<li class="gchoice_47_57_44">
							<input name="input_57.44" type="checkbox" value="33" id="choice_47_57_44">
							<label for="choice_47_57_44" id="label_47_57_44">JavaScript</label>
						</li>
						<li class="gchoice_47_57_45">
							<input name="input_57.45" type="checkbox" value="113" id="choice_47_57_45">
							<label for="choice_47_57_45" id="label_47_57_45">LMS</label>
						</li>
						<li class="gchoice_47_57_46">
							<input name="input_57.46" type="checkbox" value="208" id="choice_47_57_46">
							<label for="choice_47_57_46" id="label_47_57_46">Local development</label>
						</li>
						<li class="gchoice_47_57_47">
							<input name="input_57.47" type="checkbox" value="45" id="choice_47_57_47">
							<label for="choice_47_57_47" id="label_47_57_47">Management</label>
						</li>
						<li class="gchoice_47_57_48">
							<input name="input_57.48" type="checkbox" value="11" id="choice_47_57_48">
							<label for="choice_47_57_48" id="label_47_57_48">Marketing</label>
						</li>
						<li class="gchoice_47_57_49">
							<input name="input_57.49" type="checkbox" value="209" id="choice_47_57_49">
							<label for="choice_47_57_49" id="label_47_57_49">Mental Health</label>
						</li>
						<li class="gchoice_47_57_51">
							<input name="input_57.51" type="checkbox" value="207" id="choice_47_57_51">
							<label for="choice_47_57_51" id="label_47_57_51">Migration</label>
						</li>
						<li class="gchoice_47_57_52">
							<input name="input_57.52" type="checkbox" value="164" id="choice_47_57_52">
							<label for="choice_47_57_52" id="label_47_57_52">Monitoring</label>
						</li>
						<li class="gchoice_47_57_53">
							<input name="input_57.53" type="checkbox" value="35" id="choice_47_57_53">
							<label for="choice_47_57_53" id="label_47_57_53">Multilingual</label>
						</li>
						<li class="gchoice_47_57_54">
							<input name="input_57.54" type="checkbox" value="12" id="choice_47_57_54">
							<label for="choice_47_57_54" id="label_47_57_54">Multisite</label>
						</li>
						<li class="gchoice_47_57_55">
							<input name="input_57.55" type="checkbox" value="105" id="choice_47_57_55">
							<label for="choice_47_57_55" id="label_47_57_55">Open Educational Resources</label>
						</li>
						<li class="gchoice_47_57_56">
							<input name="input_57.56" type="checkbox" value="111" id="choice_47_57_56">
							<label for="choice_47_57_56" id="label_47_57_56">Performance</label>
						</li>
						<li class="gchoice_47_57_57">
							<input name="input_57.57" type="checkbox" value="224" id="choice_47_57_57">
							<label for="choice_47_57_57" id="label_47_57_57">Permissions</label>
						</li>
						<li class="gchoice_47_57_58">
							<input name="input_57.58" type="checkbox" value="127" id="choice_47_57_58">
							<label for="choice_47_57_58" id="label_47_57_58">Personal development</label>
						</li>
						<li class="gchoice_47_57_59">
							<input name="input_57.59" type="checkbox" value="146" id="choice_47_57_59">
							<label for="choice_47_57_59" id="label_47_57_59">Platform Selection</label>
						</li>
						<li class="gchoice_47_57_61">
							<input name="input_57.61" type="checkbox" value="13" id="choice_47_57_61">
							<label for="choice_47_57_61" id="label_47_57_61">Plugins</label>
						</li>
						<li class="gchoice_47_57_62">
							<input name="input_57.62" type="checkbox" value="206" id="choice_47_57_62">
							<label for="choice_47_57_62" id="label_47_57_62">Product Management</label>
						</li>
						<li class="gchoice_47_57_63">
							<input name="input_57.63" type="checkbox" value="156" id="choice_47_57_63">
							<label for="choice_47_57_63" id="label_47_57_63">Productivity</label>
						</li>
						<li class="gchoice_47_57_64">
							<input name="input_57.64" type="checkbox" value="134" id="choice_47_57_64">
							<label for="choice_47_57_64" id="label_47_57_64">Professional Development</label>
						</li>
						<li class="gchoice_47_57_65">
							<input name="input_57.65" type="checkbox" value="142" id="choice_47_57_65">
							<label for="choice_47_57_65" id="label_47_57_65">Project Management</label>
						</li>
						<li class="gchoice_47_57_66">
							<input name="input_57.66" type="checkbox" value="147" id="choice_47_57_66">
							<label for="choice_47_57_66" id="label_47_57_66">Publishing</label>
						</li>
						<li class="gchoice_47_57_67">
							<input name="input_57.67" type="checkbox" value="112" id="choice_47_57_67">
							<label for="choice_47_57_67" id="label_47_57_67">Research</label>
						</li>
						<li class="gchoice_47_57_68">
							<input name="input_57.68" type="checkbox" value="109" id="choice_47_57_68">
							<label for="choice_47_57_68" id="label_47_57_68">REST API</label>
						</li>
						<li class="gchoice_47_57_69">
							<input name="input_57.69" type="checkbox" value="14" id="choice_47_57_69">
							<label for="choice_47_57_69" id="label_47_57_69">Security</label>
						</li>
						<li class="gchoice_47_57_71">
							<input name="input_57.71" type="checkbox" value="36" id="choice_47_57_71">
							<label for="choice_47_57_71" id="label_47_57_71">SEO</label>
						</li>
						<li class="gchoice_47_57_72">
							<input name="input_57.72" type="checkbox" value="141" id="choice_47_57_72">
							<label for="choice_47_57_72" id="label_47_57_72">Site building</label>
						</li>
						<li class="gchoice_47_57_73">
							<input name="input_57.73" type="checkbox" value="212" id="choice_47_57_73">
							<label for="choice_47_57_73" id="label_47_57_73">Social media</label>
						</li>
						<li class="gchoice_47_57_74">
							<input name="input_57.74" type="checkbox" value="210" id="choice_47_57_74">
							<label for="choice_47_57_74" id="label_47_57_74">Software-as-a-Service</label>
						</li>
						<li class="gchoice_47_57_75">
							<input name="input_57.75" type="checkbox" value="143" id="choice_47_57_75">
							<label for="choice_47_57_75" id="label_47_57_75">Strategy</label>
						</li>
						<li class="gchoice_47_57_76">
							<input name="input_57.76" type="checkbox" value="159" id="choice_47_57_76">
							<label for="choice_47_57_76" id="label_47_57_76">Support</label>
						</li>
						<li class="gchoice_47_57_77">
							<input name="input_57.77" type="checkbox" value="107" id="choice_47_57_77">
							<label for="choice_47_57_77" id="label_47_57_77">System administration</label>
						</li>
						<li class="gchoice_47_57_78">
							<input name="input_57.78" type="checkbox" value="31" id="choice_47_57_78">
							<label for="choice_47_57_78" id="label_47_57_78">Teaching</label>
						</li>
						<li class="gchoice_47_57_79">
							<input name="input_57.79" type="checkbox" value="144" id="choice_47_57_79">
							<label for="choice_47_57_79" id="label_47_57_79">Testing</label>
						</li>
						<li class="gchoice_47_57_81">
							<input name="input_57.81" type="checkbox" value="15" id="choice_47_57_81">
							<label for="choice_47_57_81" id="label_47_57_81">Themes</label>
						</li>
						<li class="gchoice_47_57_82">
							<input name="input_57.82" type="checkbox" value="158" id="choice_47_57_82">
							<label for="choice_47_57_82" id="label_47_57_82">Upgrades</label>
						</li>
						<li class="gchoice_47_57_83">
							<input name="input_57.83" type="checkbox" value="27" id="choice_47_57_83">
							<label for="choice_47_57_83" id="label_47_57_83">Usability</label>
						</li>
						<li class="gchoice_47_57_84">
							<input name="input_57.84" type="checkbox" value="199" id="choice_47_57_84">
							<label for="choice_47_57_84" id="label_47_57_84">User experience</label>
						</li>
						<li class="gchoice_47_57_85">
							<input name="input_57.85" type="checkbox" value="135" id="choice_47_57_85">
							<label for="choice_47_57_85" id="label_47_57_85">User interface</label>
						</li>
						<li class="gchoice_47_57_86">
							<input name="input_57.86" type="checkbox" value="227" id="choice_47_57_86">
							<label for="choice_47_57_86" id="label_47_57_86">Voice Interaction</label>
						</li>
						<li class="gchoice_47_57_87">
							<input name="input_57.87" type="checkbox" value="222" id="choice_47_57_87">
							<label for="choice_47_57_87" id="label_47_57_87">Web components</label>
						</li>
						<li class="gchoice_47_57_88">
							<input name="input_57.88" type="checkbox" value="223" id="choice_47_57_88">
							<label for="choice_47_57_88" id="label_47_57_88">Workflow</label>
						</li>
						<li class="gchoice_47_57_89">
							<input name="input_57.89" type="checkbox" value="152" id="choice_47_57_89">
							<label for="choice_47_57_89" id="label_47_57_89">Writing</label>
						</li>
					</ul>
				</div>
			</fieldset>
		</li>
		<li id="field_47_56" class="gfield field_sublabel_below field_description_above gfield_visibility_visible"><label class="gfield_label" for="input_47_56">Other Session Categories</label>
			<div class="gfield_description" id="gfield_description_47_56">Please use this field to recommend session categories to add to the list that would match your session. Separate the categories by comma.</div>
			<div class="ginput_container ginput_container_text"><input name="input_56" id="input_47_56" type="text" value="" class="medium" aria-describedby="gfield_description_47_56" aria-invalid="false"></div>
		</li>
		<li id="field_47_65" class="gfield gf_list_3col field_sublabel_below field_description_below gfield_visibility_visible">
			<fieldset class="gfieldset">
				<legend class="gfield_label">What is the technical level of your presentation?</legend>
				<div class="ginput_container ginput_container_checkbox">
					<ul class="gfield_checkbox" id="input_47_65">
						<li class="gchoice_47_65_1">
							<input name="input_65.1" type="checkbox" value="121" id="choice_47_65_1">
							<label for="choice_47_65_1" id="label_47_65_1">Beginner</label>
						</li>
						<li class="gchoice_47_65_2">
							<input name="input_65.2" type="checkbox" value="122" id="choice_47_65_2">
							<label for="choice_47_65_2" id="label_47_65_2">Intermediate</label>
						</li>
						<li class="gchoice_47_65_3">
							<input name="input_65.3" type="checkbox" value="123" id="choice_47_65_3">
							<label for="choice_47_65_3" id="label_47_65_3">Advanced</label>
						</li>
					</ul>
				</div>
			</fieldset>
		</li>
		<li id="field_47_67" class="gfield gf_list_inline gfield_contains_required field_sublabel_below field_description_above gfield_visibility_visible">
			<fieldset aria-required="true" class="gfieldset">
				<legend class="gfield_label">Preferred Session Format<span class="gfield_required"> * <span class="sr-only"> Required</span></span></legend>
				<div class="gfield_description" id="gfield_description_47_67">Which formats do you think are best for your topic?<br><strong>Workshops will be held on Thursday, July 25. All other formats on July 26-27.</strong></div>
				<div class="ginput_container ginput_container_checkbox">
					<ul class="gfield_checkbox" id="input_47_67">
						<li class="gchoice_47_67_1">
							<input name="input_67.1" type="checkbox" value="218" id="choice_47_67_1">
							<label for="choice_47_67_1" id="label_47_67_1">General Lecture Session</label>
						</li>
						<li class="gchoice_47_67_2">
							<input name="input_67.2" type="checkbox" value="219" id="choice_47_67_2">
							<label for="choice_47_67_2" id="label_47_67_2">Hands-on Workshop</label>
						</li>
						<li class="gchoice_47_67_3">
							<input name="input_67.3" type="checkbox" value="220" id="choice_47_67_3">
							<label for="choice_47_67_3" id="label_47_67_3">Lightning Talk</label>
						</li>
						<li class="gchoice_47_67_4">
							<input name="input_67.4" type="checkbox" value="221" id="choice_47_67_4">
							<label for="choice_47_67_4" id="label_47_67_4">Panel Discussion</label>
						</li>
					</ul>
				</div>
			</fieldset>
		</li>
		<li id="field_47_68" class="gfield gfield_html gfield_html_formatted field_sublabel_below field_description_below gfield_visibility_visible" style="display: none;">
			<div class="panel primary"><strong>Reminder:</strong> Workshops will be held on Thursday, July 25. All other formats on July 26-27.</div>
		</li>
		<li id="field_47_26" class="gfield field_sublabel_below field_description_above gfield_visibility_visible"><label class="gfield_label" for="input_47_26">Tell us about your experience in the subject and past speaking engagements.</label>
			<div class="gfield_description" id="gfield_description_47_26">Professional speaking experience isn't required, but pass along any recent or relevant history. If multiple speakers, please include information for everyone, including whether or not you've presented together before.</div>
			<div class="ginput_container ginput_container_textarea"><textarea name="input_26" id="input_47_26" class="textarea medium" aria-describedby="gfield_description_47_26" maxlength="1500" aria-invalid="false" rows="10" cols="50"></textarea>
				<div class="charleft ginput_counter">0 of 1500 max characters</div>
			</div>
		</li>
		<li id="field_47_19" class="gfield field_sublabel_below field_description_below gfield_visibility_visible"><label class="gfield_label" for="input_47_19">Is there anything else we should know to make a good decision about your proposal? Any general notes or comments?</label>
			<div class="ginput_container ginput_container_textarea"><textarea name="input_19" id="input_47_19" class="textarea medium" maxlength="1500" aria-invalid="false" rows="10" cols="50"></textarea>
				<div class="charleft ginput_counter">0 of 1500 max characters</div>
			</div>
		</li>
		<li id="field_47_30" class="gfield gf_list_inline gfield_contains_required field_sublabel_below field_description_below gfield_visibility_visible">
			<fieldset aria-required="true" class="gfieldset">
				<legend class="gfield_label">Do you need any special accommodation (sign language interpreting, etc) to participate in WPCampus?<span class="gfield_required"> * <span class="sr-only"> Required</span></span></legend>
				<div class="ginput_container ginput_container_radio">
					<ul class="gfield_radio" id="input_47_30">
						<li class="gchoice_47_30_0"><input name="input_30" type="radio" value="Yes" id="choice_47_30_0"><label for="choice_47_30_0" id="label_47_30_0">Yes</label></li>
						<li class="gchoice_47_30_1"><input name="input_30" type="radio" value="No" id="choice_47_30_1"><label for="choice_47_30_1" id="label_47_30_1">No</label></li>
					</ul>
				</div>
			</fieldset>
		</li>
		<li id="field_47_27" class="gfield field_sublabel_below field_description_below gfield_visibility_visible" style="display: none;"><label class="gfield_label" for="input_47_27">Please specify in detail about the special accommodations required.</label>
			<div class="ginput_container ginput_container_textarea"><textarea name="input_27" id="input_47_27" class="textarea medium" aria-invalid="false" rows="10" cols="50" disabled=""></textarea></div>
		</li>
		<li id="field_47_69" class="gfield gfield_contains_required field_sublabel_below field_description_above gfield_visibility_visible"><label class="gfield_label">WPCampus Code of Conduct<span class="gfield_required"> * <span class="sr-only"> Required</span></span></label>
			<div class="gfield_description" id="gfield_description_47_69">WPCampus seeks to provide a friendly, inclusive environment and requires all participants to adhere to our Code of Conduct, which applies to all conference events.<br><br>Please read the <a href="https://wpcampus.org/code-of-conduct/" target="_blank" title="this link will open in a new window">WPCampus Code of Conduct</a> and acknowledge:</div>
			<div id="gw_terms_69" class="large gptos_terms_container gwtos_terms_container" tabindex="0">
				<div class="gptos_the_terms"><p>WPCampus seeks to provide a friendly, safe environment in which all participants can engage in productive dialogue, sharing, and learning with each other in an atmosphere of mutual respect. We are committed to providing a harassment-free environment for all, regardless of gender, gender identity and expression, age, sexual orientation, disability, physical appearance, body size, race, ethnicity, religion (or lack thereof), or technology
						choices.</p>
					<p>In order to promote such an environment, we require all participants to adhere to the following code of conduct. This code of conduct outlines our expectations for behavior within all WPCampus interaction and events (whether online or in-person), what to do if you witness or are subject to unacceptable behavior, and how community leadership will respond.</p>
					<p>This code applies to all WPCampus interaction and events (whether online or in-person).</p>
					<h2>Diversity, equity, and inclusion</h2>
					<p>The mission of WPCampus is to advance and support higher education. We believe the growth of education and research are most powerful when its voices are diverse.</p>
					<p>Our code of conduct exists to support our community’s <a href="https://wpcampus.org/diversity/">statement on diversity, equity, and inclusion</a> and is meant to enforce and provide processes for instances of violations in our community.</p>
					<h2>Expected Behavior</h2>
					<ul>
						<li>Treat other participants with respect</li>
						<li>Critical dialogue is useful, but should be constructive and collaborative</li>
						<li>Be aware of what is happening around you, and if you see anyone in distress, or witness unacceptable behavior, please <a href="https://wpcampus.org/code-of-conduct/report/">contact community leadership</a>.</li>
					</ul>
					<h2>Unacceptable Behavior</h2>
					<ul>
						<li>WPCampus does not tolerate intimidating, harassing, abusive, discriminatory, derogatory or demeaning conduct by any participants in WPCampus interaction and events.</li>
						<li>Examples of unacceptable behavior include:
							<ul>
								<li>offensive verbal comments related to gender, gender identity and expression, age, sexual orientation, disability, physical appearance, body size, race, ethnicity, religion (or lack thereof), or technology choices</li>
								<li>inappropriate use of nudity and/or sexual images, activities, or other sexual material</li>
								<li>sexualized clothing/uniforms/costumes, or otherwise creating a sexualized environment</li>
								<li>deliberate intimidation, stalking or following</li>
								<li>harassing photography or recording</li>
								<li>sustained disruption of talks, meetings, or other events</li>
								<li>inappropriate physical contact</li>
								<li>unwelcome sexual attention</li>
								<li>advocating for, or encouraging, any of the above behavior</li>
							</ul>
						</li>
						<li>To clarify, critical examination of beliefs and viewpoints does not, by itself, constitute hostile conduct or harassment. Similarly, use of sexual imagery or language in the context of a professional discussion might not constitute hostile conduct or harassment.</li>
					</ul>
					<h2>What to do if you witness or are subject to unacceptable behavior</h2>
					<p>Please contact community leadership. If preferable, you can <a href="https://wpcampus.org/code-of-conduct/report/">submit a report online</a>,&nbsp;which allows for anonymity.</p>
					<p><a class="button expanded" href="https://wpcampus.org/code-of-conduct/report/">Submit a code of conduct report</a></p>
					<h3>Anonymous report</h3>
					<p>You can <a href="https://wpcampus.org/code-of-conduct/report/">submit an anonymous report</a>. This form includes fields for identifying information but they are not required.</p>
					<p>We can’t follow up an anonymous report with you directly, but we will fully investigate and take whatever action is necessary to prevent a recurrence.</p>
					<h3>Personal report</h3>
					<p>You can make a personal report by:</p>
					<ul>
						<li><a href="https://wpcampus.org/code-of-conduct/report/">Submitting a conduct report online</a> and including identifying info</li>
						<li>Message WPCampus staff via&nbsp;email or&nbsp;<a href="https://wordcampus.slack.com/">direct message in Slack</a></li>
					</ul>
					<p>When taking a personal report, we will ensure you are safe and cannot be overheard. They may involve community leadership to ensure your report is managed properly. Once safe, we’ll ask you to tell us about what happened. This can be upsetting, but we’ll handle it as respectfully as possible, and you can bring someone to support you. You won’t be asked to confront anyone and we won’t tell anyone who you are.</p>
					<h2>Consequences of unacceptable behavior</h2>
					<p>Those found to be engaging in unacceptable behavior will be asked to stop immediately, and are expected to comply. Depending on the severity of the behavior, and the need to protect participants’ safety, those who violate this policy may be expelled from the community.</p>
					<h2>Attribution and license</h2>
					<p>This Code of Conduct is released under a <a href="https://creativecommons.org/licenses/by-sa/3.0/">Creative Commons Attribution-ShareAlike license</a>.</p>
					<p>It’s language was inspired by several others, including:</p>
					<ul>
						<li><a href="http://confcodeofconduct.com/">http://confcodeofconduct.com/</a>, licensed <a href="https://creativecommons.org/licenses/by/3.0/deed.en_US">CC BY 3.0 unported</a></li>
						<li><a href="https://make.wordpress.org/community/handbook/wordcamp-organizer-handbook/planning-details/code-of-conduct/">WordCamp Code of Conduct</a>, licensed <a href="https://creativecommons.org/licenses/by-sa/3.0/">CC BY-SA 3.0 unported</a></li>
						<li><a href="http://geekfeminism.wikia.com/wiki/Conference_anti-harassment/Policy">Geek Feminism Wiki Conference Code of Conduct</a>, licensed CC0</li>
						<li><a href="http://alamw14.ala.org/statement-of-appropriate-conduct">American Library Association</a> (some wording used by permission)</li>
					</ul>
					<hr>
					<p><em>Last modified:</em> May 23, 2019 by <a href="https://wpcampus.org/author/bamadesigner/">Rachel Cherry</a></p>
				</div>
			</div>
			<style type="text/css">

				/* Frontend Styles */
				.gptos_terms_container {
					height: 11.250em;
					width: 97.5%;
					background-color: #fff;
					overflow: auto;
					border: 1px solid #ccc;
				}

				.gptos_terms_container.small {
					width: 25%;
				}

				.gptos_terms_container.medium {
					width: 47.5%;
				}

				.gptos_terms_container.large { /* default width */
				}

				.left_label .gptos_terms_container,
				.right_label .gptos_terms_container {
					margin-left: 30% !important;
					width: auto !important;
				}

				.gform_wrapper .gptos_terms_container > div {
					margin: 1rem !important;
				}

				.gform_wrapper .gptos_terms_container ul,
				.gform_wrapper .gptos_terms_container ol {
					margin: 0 0 1rem 1.5rem !important;
				}

				.gform_wrapper .gptos_terms_container ul li {
					list-style: disc !important;
				}

				.gform_wrapper .gptos_terms_container ol li {
					list-style: decimal !important;
				}

				.gform_wrapper .gptos_terms_container p {
					margin: 0 0 1rem;
				}

				.gform_wrapper .gptos_terms_container *:last-child {
					margin-bottom: 0;
				}

				/* Admin Styles */
				#gform_fields .gptos_terms_container {
					background-color: rgba(255, 255, 255, 0.5);
					border-color: rgba(222, 222, 222, 0.75);
				}

				#gform_fields .gptos_terms_container > div {
					margin: 1rem !important;
				}

				#gform_fields .gptos_terms_container p {
					margin: 0 0 1rem;
				}

				#gform_fields .gptos_terms_container *:last-child {
					margin-bottom: 0;
				}

			</style>

			<div class="ginput_container" style="margin-top:12px;">
				<ul class="gfield_checkbox" id="input_47_69">
					<li class="gchoice_47_69_1">
						<input name="input_69.1" type="checkbox" value="I agree to adhere to the WPCampus Code of Conduct" id="choice_47_69_1" disabled="disabled">
						<label for="choice_47_69_1" id="label_47_69_1">I agree to adhere to the WPCampus Code of Conduct</label>
					</li>
				</ul>
			</div>
		</li>
	</ul>
	<?php
}

function wpcampus_print_gravity_form() {

	$form_id = 47;
	$form    = wpcampus_get_gravity_form( $form_id );

	if ( empty( $form ) || is_wp_error( $form ) ) {
		return;
	}

	/*
	 * @TODO:
	 * - Add action
	 */

	$form_id = "wpc-gform-{$form_id}";

	$form_class = [ 'wpc-gform' ];

	if ( ! empty( $form->cssClass ) ) {
		$form_class[] = $form->cssClass;
	}

	if ( ! empty( $form_class ) ) {
		$form_class_str = ' class="' . implode( ' ', $form_class ) . '"';
	} else {
		$form_class_str = '';
	}

	// @TODO do we need?
	//  enctype="multipart/form-data"

	?>
	<form method="post" action="" id="<?php echo $form_id; ?>"<?php echo $form_class_str; ?>>
		<div class="gform_body">
			<?php wpcampus_print_gravity_fields( $form_id, $form->fields ); ?>
		</div>
		<div class="gform_footer top_label"><input type="submit" id="gform_submit_button_47" class="gform_button button" value="Submit Your Proposal" onclick="if(window[&quot;gf_submitting_47&quot;]){return false;}  window[&quot;gf_submitting_47&quot;]=true;  " onkeypress="if( event.keyCode == 13 ){ if(window[&quot;gf_submitting_47&quot;]){return false;} window[&quot;gf_submitting_47&quot;]=true;  jQuery(&quot;#gform_47&quot;).trigger(&quot;submit&quot;,[true]); }">
			<input type="hidden" class="gform_hidden" name="is_submit_47" value="1">
			<input type="hidden" class="gform_hidden" name="gform_submit" value="47">

			<input type="hidden" class="gform_hidden" name="gform_unique_id" value="">
			<input type="hidden" class="gform_hidden" name="state_47" value="WyJbXSIsImFjNTk4MmY0NzQ2YjZlNTI5MjAyNzAzYWUzZGUxNDg0Il0=">
			<input type="hidden" class="gform_hidden" name="gform_target_page_number_47" id="gform_target_page_number_47" value="0">
			<input type="hidden" class="gform_hidden" name="gform_source_page_number_47" id="gform_source_page_number_47" value="1">
			<input type="hidden" name="gform_field_values" value="">

		</div>
	</form>
	<?php
}

//add_filter( 'the_content', 'wpcampus_print_gravity_form' );