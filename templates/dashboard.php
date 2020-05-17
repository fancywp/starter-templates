<?php
//var_dump( $this->get_activated_plugins() );

//Starter_Templates_Ajax::download_file('https://raw.githubusercontent.com/fancywp/fancywp-xml-demos/master/starterblog/config.json');

?>
<div id="starter-templates-filter" class="wp-filter hide-if-no-js">
    <div class="filter-count">
        <span id="starter-templates-filter-count" class="count theme-count">&#45;</span>
    </div>
    <ul id="starter-templates-filter-cat" class="filter-links">
        <li><a href="#" data-slug="all" class="current"><?php _e( 'All', 'starter-templates' ); ?></a></li>
    </ul>
    <form class="search-form">
        <label class="screen-reader-text" for="wp-filter-search-input"><?php _e( 'Search Themes', 'starter-templates' ); ?></label><input placeholder="<?php esc_attr_e( 'Search sites...', 'starter-templates' ); ?>" type="search" aria-describedby="live-search-desc" id="starter-templates-search-input" class="wp-filter-search">
    </form>
    <ul id="starter-templates-filter-tag"  class="filter-links float-right" style="float: right;"></ul>
</div>


<script id="starter-template-item-html" type="text/html">
    <div class="theme" title="{{ data.title }}" tabindex="0" aria-describedby="" data-slug="{{ data.slug }}">
        <div class="theme-screenshot">
            <img src="{{ data.thumbnail_url }}" alt="">
        </div>
        <#  if ( data.pro ) {  #>
        <span class="theme-pro-bubble"><?php _e( 'Pro', 'starter-templates' ); ?></span>
        <# } #>
        <div class="theme-id-container">
            <h2 class="theme-name" id="{{ data.slug }}-name">{{ data.title }}</h2>
            <div class="theme-actions">
                <a class="cs-open-preview button button-secondary  hide-if-no-customize" data-slug="{{ data.slug }}" href="#"><?php _e( 'Preview', 'starter-templates' ); ?></a>
                <a class="cs-open-modal button button-primary  hide-if-no-customize" href="#"><?php _e( 'Details', 'starter-templates' ); ?></a>
            </div>
        </div>
    </div>
</script>


<div id="starter-templates-listing-wrapper" class="theme-browser rendered">
    <div id="starter-templates-listing" class="themes wp-clearfix">
    </div>
</div>
<p  id="starter-templates-no-demos"  class="no-themes"><?php _e( 'No sites found. Try a different search.', 'starter-templates' ); ?></p>
<span class="spinner"></span>


