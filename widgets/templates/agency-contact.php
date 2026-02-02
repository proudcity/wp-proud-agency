<div class="agency-contact-widget"><!-- /.wp-proud-agency/widgets/templates/agency-contact.php -->
    <?php if ($name): ?><div class="row field-contact-name">
            <div class="col-xs-2"><i aria-hidden="true" class="fa fa-user fa-2x text-muted"></i></div>
            <div class="col-xs-10">
                <?php if (!empty($name_link)): ?>
                    <?php print sprintf('<a href="%s" rel="bookmark">%s</a>', $name_link, esc_html($name)); ?>
                <?php else: ?>
                    <?php print esc_html($name) ?>
                <?php endif; ?>
                <?php if (!empty($name_title)): ?><div><?php print esc_html($name_title) ?></div><?php endif; ?>
                <hr />
            </div>
        </div><?php endif; ?>

    <?php if ($phone): ?><div class="row field-contact-phone">
            <div class="col-xs-2"><i aria-hidden="true" class="fa fa-phone fa-2x text-muted"></i></div>
            <div class="col-xs-10">
                <?php print Proud\Agency\AgencyContact::phone_tel_links($phone) ?></a>
                <hr />
            </div>
        </div><?php endif; ?>

    <?php if ($fax): ?><div class="row field-contact-fax">
            <div class="col-xs-2"><i aria-hidden="true" class="fa fa-fax fa-2x text-muted"></i></div>
            <div class="col-xs-10">
                <?php print Proud\Agency\AgencyContact::phone_tel_links($fax) ?> (FAX)
                <hr />
            </div>
        </div><?php endif; ?>

    <?php if ($sms): ?><div class="row field-contact-sms">
            <div class="col-xs-2"><i aria-hidden="true" class="fa fa-mobile fa-2x text-muted"></i></div>
            <div class="col-xs-10">
                <?php print Proud\Agency\AgencyContact::phone_tel_links($sms, 'sms') ?> (Text)
                <hr />
            </div>
        </div><?php endif; ?>

    <?php if ($email): ?><div class="row field-contact-email">
            <div class="col-xs-2"><i aria-hidden="true" class="fa fa-2x text-muted fa-<?php if (filter_var($email, FILTER_VALIDATE_EMAIL)): ?>envelope<?php else: ?>external-link<?php endif; ?>"></i></div>
            <div class="col-xs-10">
                <?php print Proud\Agency\AgencyContact::email_mailto_links($email) ?>
                <hr />
            </div>
        </div><?php endif; ?>

    <?php if ($address): ?><div class="row field-contact-address">
            <div class="col-xs-2"><i aria-hidden="true" class="fa fa-map-marker fa-2x text-muted"></i></div>
            <div class="col-xs-10">
                <?php print nl2br(esc_html($address)) ?>
                <hr />
            </div>
        </div><?php endif; ?>

    <?php if ($hours): ?><div class="row field-contact-hours">
            <div class="col-xs-2"><i aria-hidden="true" class="fa fa-clock-o fa-2x text-muted"></i></div>
            <div class="col-xs-10">
                <?php print nl2br(esc_html($hours)) ?>
                <hr />
            </div>
        </div><?php endif; ?>

    <?php
    /**
     * Extract non-empty social links from $instance.
     * - Whitelist allowed services to prevent unexpected icon/classes/labels.
     * - Normalize + validate URLs (http/https; allow webcal for iCal if you want).
     */

    // Services we support + their FA icon name (where it differs from service key).
    $icon_map = [
        'facebook'  => 'facebook',
        'twitter'   => 'twitter',
        'x'         => 'x-twitter',
        'instagram' => 'instagram',
        'youtube'   => 'youtube',
        'rss'       => 'rss',
        'ical'      => 'calendar',   // solid icon
        'nextdoor'  => 'door-open',  // solid icon
        'tiktok'    => 'tiktok',
        'snapchat'  => 'snapchat',
    ];

    $solid_icons = ['rss' => true, 'ical' => true, 'nextdoor' => true];

    // If you prefer square-* for some brands, list those that should NOT be squared.
    $no_square = ['instagram' => true, 'x' => true, 'ical' => true, 'tiktok' => true, 'nextdoor' => true];

    $socialLinks = [];

    foreach ($instance as $key => $value) {
        // Only process string keys like "social_facebook".
        if (!is_string($key) || !str_starts_with($key, 'social_')) {
            continue;
        }

        $service = substr($key, 7); // strlen('social_') === 7
        if ($service === '' || !isset($icon_map[$service])) {
            continue; // Ignore unknown/unsupported services.
        }

        $url = trim((string) $value);
        if ($url === '') {
            continue;
        }

        // Hard validation: allow http(s). Optionally allow webcal for iCal feeds.
        $scheme = (string) wp_parse_url($url, PHP_URL_SCHEME);
        $allowed_schemes = ($service === 'ical') ? ['http', 'https', 'webcal'] : ['http', 'https'];
        if ($scheme && !in_array(strtolower($scheme), $allowed_schemes, true)) {
            continue;
        }

        $socialLinks[$service] = $url;
    }

    if ($socialLinks): ?>
        <div class="row field-contact-social">
            <div class="col-xs-2">
                <i aria-hidden="true" class="fa fa-share-alt fa-2x text-muted"></i>
            </div>

            <div class="col-xs-10">
                <ul class="list-unstyled">
                    <?php foreach ($socialLinks as $service => $url):
                        $icon_name = $icon_map[$service];
                        $fa_style  = isset($solid_icons[$service]) ? 'fa-solid' : 'fa-brands';
                        $fa_icon   = isset($no_square[$service]) ? $icon_name : 'square-' . $icon_name;
                        $label     = ucfirst($service);
                    ?>
                        <li>
                            <a href="<?php echo esc_url($url, ($service === 'ical') ? ['http', 'https', 'webcal'] : ['http', 'https']); ?>"
                                title="<?php echo esc_attr($label); ?>"
                                target="_blank"
                                rel="noopener noreferrer">
                                <i aria-hidden="true" class="<?php echo esc_attr('fa ' . $fa_style . ' fa-' . $fa_icon); ?>"></i>
                                <?php echo esc_html($label); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>

</div>

<style>
    .agency-contact-widget hr {
        margin: 16px 0;
    }
</style>
