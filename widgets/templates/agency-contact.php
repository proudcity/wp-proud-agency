<div class="agency-contact-widget">
  <?php if($name): ?><div class="row field-contact-name">
    <div class="col-xs-2"><i aria-hidden="true" class="fa fa-user fa-2x text-muted"></i></div>
    <div class="col-xs-10">
      <?php if( !empty($name_link) ): ?>
        <?php print sprintf( '<a href="%s" rel="bookmark">%s</a>', esc_url( $name_link ), esc_html($name) ); ?>
      <?php else: ?>
        <?php print esc_html($name) ?>
      <?php endif; ?>
      <?php if( !empty($name_title) ): ?><div><?php print esc_html($name_title) ?></div><?php endif; ?>
      <hr/>
    </div>
  </div><?php endif; ?>

  <?php if($phone): ?><div class="row field-contact-phone">
    <div class="col-xs-2"><i aria-hidden="true" class="fa fa-phone fa-2x text-muted"></i></div>
    <div class="col-xs-10">
      <?php print Proud\Agency\AgencyContact::phone_tel_links($phone) ?></a>
      <hr/>
    </div>
  </div><?php endif; ?>

  <?php if($fax): ?><div class="row field-contact-fax">
    <div class="col-xs-2"><i aria-hidden="true" class="fa fa-fax fa-2x text-muted"></i></div>
    <div class="col-xs-10">
      <?php print Proud\Agency\AgencyContact::phone_tel_links($fax) ?> (FAX)
      <hr/>
    </div>
  </div><?php endif; ?>

  <?php if($email): ?><div class="row field-contact-email">
    <div class="col-xs-2"><i aria-hidden="true" class="fa fa-2x text-muted fa-<?php if(filter_var( $email, FILTER_VALIDATE_EMAIL ) ): ?>envelope<?php else: ?>external-link<?php endif; ?>"></i></div>
    <div class="col-xs-10">
      <?php print Proud\Agency\AgencyContact::email_mailto_links($email) ?>
      <hr/>
    </div>
  </div><?php endif; ?>

  <?php if($address): ?><div class="row field-contact-address">
    <div class="col-xs-2"><i aria-hidden="true" class="fa fa-map-marker fa-2x text-muted"></i></div>
    <div class="col-xs-10">
      <?php print nl2br(esc_html($address)) ?>
      <hr/>
    </div>
  </div><?php endif; ?>

  <?php if($hours): ?><div class="row field-contact-hours">
    <div class="col-xs-2"><i aria-hidden="true" class="fa fa-clock-o fa-2x text-muted"></i></div>
    <div class="col-xs-10">
      <?php print nl2br(esc_html($hours)) ?>
      <hr/>
    </div>
  </div><?php endif; ?>

  <?php if( !empty( $instance['social'] ) ): ?><div class="row field-contact-social">
    <div class="col-xs-2"><i aria-hidden="true" class="fa fa-share-alt fa-2x text-muted"></i></div>
    <div class="col-xs-10">
      <ul class="list-unstyled">
      <?php foreach ($instance['social'] as $service => $url): ?>
        <li>
          <a href="<?php print $url; ?>" title="<?php print ucfirst($service); ?>" target="_blank"><i aria-hidden="true" class="fa fa-fw fa-<?php print $service == 'instagram' ? $service : $service.'-square'; ?>"></i><?php print ucfirst($service); ?></a>
        </li>
      <?php endforeach; ?>
      </ul>
    </div>
  </div><?php endif; ?>

</div>

<style>
  .agency-contact-widget hr {
    margin: 16px 0;
  }
</style>