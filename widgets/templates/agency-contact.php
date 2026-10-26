<div class="agency-contact-widget"><!-- /.wp-proud-agency/widgets/templates/agency-contact.php -->
    <?php if ($name): ?><div class="row field-contact-name">
            <div class="col-xs-2"><i aria-hidden="true" class="fa fa-user fa-2x text-muted"></i></div>
            <div class="col-xs-10">
                <?php if (!empty($name_link)): ?>
                    <?php // CustomContact passes name_link straight through from a widget
                          // field, so escape here rather than trusting the caller. ?>
                    <?php print sprintf('<a href="%s" rel="bookmark">%s</a>', esc_url($name_link), esc_html($name)); ?>
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

    <?php if (!empty($sms)): ?><div class="row field-contact-sms">
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
     * Collect non-empty social links from $instance.
     *
     * Both widgets that use this template put the accounts on $instance as flat
     * social_<service> keys: CustomContact from its own widget fields,
     * AgencyContact from post meta via agency_social_meta(). The icon mapping
     * and scheme allowlist live in wp-proud-agency.php so this template and the
     * standalone AgencySocial widget cannot drift apart again.
     */
    $socialLinks = [];

    foreach ($instance as $key => $value) {
        if (!is_string($key) || !str_starts_with($key, 'social_')) {
            continue;
        }

        $service = substr($key, 7); // strlen('social_') === 7
        if (Proud\Agency\social_icon_classes($service) === null) {
            continue; // Unknown/unsupported service.
        }

        $url = trim((string) $value);
        if ($url === '') {
            continue;
        }

        // Reject anything that is not an allowed scheme for this service.
        $scheme = (string) wp_parse_url($url, PHP_URL_SCHEME);
        if ($scheme && !in_array(strtolower($scheme), Proud\Agency\social_allowed_schemes($service), true)) {
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
                        $label = ucfirst($service);
                    ?>
                        <li>
                            <a href="<?php echo esc_url($url, Proud\Agency\social_allowed_schemes($service)); ?>"
                                title="<?php echo esc_attr($label); ?>"
                                target="_blank"
                                rel="noopener noreferrer">
                                <i aria-hidden="true" class="<?php echo esc_attr(Proud\Agency\social_icon_classes($service)); ?>"></i>
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
