<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Generates a stable, distinct-looking colour for a swatch from its term
 * name (hash -> HSL), for stores that haven't installed a colour-swatch
 * plugin that stores real hex values in term meta. If your store DOES use
 * one (checked below via common meta keys), the real colour is used
 * instead — this fallback only fires when no real colour data exists.
 */
function seiridge_term_swatch_color( $term ) {
	foreach ( array( 'product_attribute_color', '_color', 'color' ) as $meta_key ) {
		$val = get_term_meta( $term->term_id, $meta_key, true );
		if ( $val ) return $val;
	}
	$hash = crc32( $term->name );
	$hue = $hash % 360;
	return 'hsl(' . $hue . ', 45%, 45%)';
}

/**
 * Renders the ORIGINAL swatch-row / variant-pill-row markup, populated
 * from the real product's actual variation attributes — whatever they're
 * named, however many there are (the original design demos exactly two
 * groups; a 3rd+ attribute reuses the pill-row style, generalising the
 * pattern rather than dropping the data). Each control carries
 * data-wc-attribute (the real WC field name, e.g. "attribute_pa_color")
 * and data-slug (the real option value) so the JS bridge below can drive
 * WooCommerce's own hidden <select> — no hardcoded label matching.
 */
function seiridge_render_variation_selectors( $product ) {
	if ( ! $product->is_type( 'variable' ) ) return array( 'html' => '', 'has_variations' => false );

	$attributes = $product->get_variation_attributes(); // [ 'attribute_pa_color' => ['Red','Blue',...], ... ]
	if ( ! $attributes ) return array( 'html' => '', 'has_variations' => false );

	$blocks = '';
	$i = 0;
	foreach ( $attributes as $attribute_name => $options ) {
		$taxonomy = str_replace( 'attribute_', '', $attribute_name );
		$label = wc_attribute_label( $taxonomy, $product );
		$is_swatch_style = ( $i === 0 ); // first attribute gets the colour-swatch treatment, matching the original's visual pattern; others get pill buttons
		$i++;

		$items = '';
		foreach ( $options as $option_slug ) {
			$term = taxonomy_exists( $taxonomy ) ? get_term_by( 'slug', $option_slug, $taxonomy ) : null;
			$display = $term ? $term->name : $option_slug;

			if ( $is_swatch_style ) {
				$color = $term ? seiridge_term_swatch_color( $term ) : '#999';
				$items .= '<span class="swatch" style="background:' . esc_attr( $color ) . ';" '
					. 'data-wc-attribute="' . esc_attr( $attribute_name ) . '" data-slug="' . esc_attr( $option_slug ) . '" '
					. 'title="' . esc_attr( $display ) . '" role="button" tabindex="0"></span>';
			} else {
				$items .= '<button type="button" class="variant-pill" data-wc-attribute="' . esc_attr( $attribute_name ) . '" data-slug="' . esc_attr( $option_slug ) . '">' . esc_html( $display ) . '</button>';
			}
		}

		$blocks .= '<div class="pdp-option-group" data-group-attribute="' . esc_attr( $attribute_name ) . '">'
			. '<h4>' . esc_html( $label ) . '</h4>'
			. '<div class="' . ( $is_swatch_style ? 'swatch-row' : 'variant-row' ) . '">' . $items . '</div>'
		. '</div>';
	}

	return array( 'html' => $blocks, 'has_variations' => true );
}

/**
 * Generic JS bridge: click a swatch/pill -> set the matching REAL WC
 * variation <select> (matched by data-wc-attribute, not by attribute name
 * text) -> dispatch 'change' so WooCommerce's own variation-form.js
 * recalculates price/availability/variation_id. Also listens for WC's real
 * `found_variation` event to update the original design's main image and
 * price display, and disables swatches/pills WC's own logic has marked
 * unavailable (invalid-combination prevention, inherited from WC itself
 * rather than reimplemented).
 */
function seiridge_variation_bridge_script() {
	return <<<'JS'
<script>
(function(){
  function findSelect(attrName){
    return document.querySelector('form.variations_form select[name="' + attrName + '"]');
  }
  function selectValueForSlug(select, slug){
    for (var i=0;i<select.options.length;i++){
      if (select.options[i].value === slug) return select.options[i].value;
    }
    return null;
  }
  document.querySelectorAll('.pdp-option-group[data-group-attribute]').forEach(function(group){
    var attrName = group.getAttribute('data-group-attribute');
    var select = findSelect(attrName);
    if (!select) return;
    group.querySelectorAll('[data-wc-attribute][data-slug]').forEach(function(el){
      el.addEventListener('click', function(){
        if (el.classList.contains('disabled')) return;
        var val = selectValueForSlug(select, el.getAttribute('data-slug'));
        if (val === null) return;
        group.querySelectorAll('[data-wc-attribute][data-slug]').forEach(function(x){ x.classList.remove('active'); });
        el.classList.add('active');
        select.value = val;
        select.dispatchEvent(new Event('change', {bubbles:true}));
      });
    });
    // Reflect WooCommerce's own disabled/invalid-combination <option> state onto our swatches/pills.
    var syncAvailability = function(){
      group.querySelectorAll('[data-wc-attribute][data-slug]').forEach(function(el){
        var opt = Array.prototype.find.call(select.options, function(o){ return o.value === el.getAttribute('data-slug'); });
        el.classList.toggle('disabled', !!(opt && opt.disabled));
      });
    };
    select.addEventListener('woocommerce_variation_select_change', syncAvailability);
    var observer = new MutationObserver(syncAvailability);
    observer.observe(select, {attributes:true, childList:true, subtree:true});
  });

  var form = document.querySelector('form.variations_form');
  if (form) {
    form.addEventListener('found_variation', function(e){
      var v = e.detail || (e.originalEvent && e.originalEvent.detail);
      if (!v) return;
      if (v.image && v.image.src) {
        var main = document.getElementById('galleryMain');
        if (main) { main.style.backgroundImage = "url('" + v.image.src + "')"; main.style.backgroundSize = 'cover'; main.style.backgroundPosition = 'center'; }
      }
      if (v.price_html) {
        var priceRow = document.querySelector('.pdp-price-row');
        if (priceRow) priceRow.innerHTML = v.price_html;
      }
    });
  }
})();
</script>
JS;
}

/** CSS for the .disabled state used by the availability sync above (kept minimal — dims, doesn't redesign). */
function seiridge_variation_bridge_css() {
	return '<style>.swatch.disabled,.variant-pill.disabled{opacity:.3;pointer-events:none;position:relative;}.swatch.disabled::after{content:"";position:absolute;inset:0;background:repeating-linear-gradient(45deg,transparent,transparent 3px,rgba(0,0,0,.4) 3px,rgba(0,0,0,.4) 4px);border-radius:50%;}</style>';
}
