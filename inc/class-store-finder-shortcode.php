<?php
if ( ! defined( 'ABSPATH' ) ) exit; 
class Store_Finder_Shortcode
{
  public function __construct()
  {
    add_shortcode("store-finder", [$this, "strfn_shortcode_callback_function"]);
    add_action('wp_enqueue_scripts', [$this, 'wpEnqueueScripts']);
    add_action('wp_ajax_get_continent_based_country', array($this, 'get_continent_based_country_callback'));
    add_action('wp_ajax_nopriv_get_continent_based_country', array($this, 'get_continent_based_country_callback'));
    add_action('wp_ajax_get_data_based_country_continent', array($this, 'get_data_based_country_continent_callback'));
    add_action('wp_ajax_nopriv_get_data_based_country_continent', array($this, 'get_data_based_country_continent_callback'));
    add_action('wp_ajax_get_data_based_post_code', array($this, 'get_data_based_post_code_callback'));
    add_action('wp_ajax_nopriv_get_data_based_post_code', array($this, 'get_data_based_post_code_callback'));
  }

  public function wpEnqueueScripts()
  {
    wp_enqueue_script("jquery");

    if (!is_admin())
    {
      wp_enqueue_style('strfn-frontend-css', STOREFIND_DIR_URL . 'assets/css/frontend.css', [], STOREFIND_VERSION, "screen");
      wp_enqueue_style('strfn-fontawesome-css', STOREFIND_DIR_URL . 'assets/css/font-awesome.min.css', [], STOREFIND_VERSION, "screen");
    }
    wp_enqueue_script('strfn-frontend-js', STOREFIND_DIR_URL . 'assets/js/frontend.js', [], STOREFIND_VERSION, true);

    $nonces = array(
      'action_1' => wp_create_nonce('continent_based_country_nonce'),
      'action_2' => wp_create_nonce('country_continent_based_nonce'),
      'action_3' => wp_create_nonce('get_data_based_post_code_nonce'),
      // Add more nonces as needed
    );

    wp_localize_script('strfn-frontend-js', 'ajax_obj', array('ajaxurl' => admin_url('admin-ajax.php'), 'nonces' => $nonces, ));
  }

  public function strfn_shortcode_callback_function()
  {

    ob_start();
    ?>
    <div class="select_box">
      <div class="select_box_content">
        <div class="select_content active ">
          <div class="select_child_box">
            <select name="" id="getContinent">
              <option value="">Continent*</option>
              <option value="Africa">Africa</option>
              <option value="Antarctica">Antarctica</option>
              <option value="Asia">Asia</option>
              <option value="Europe">Europe</option>
              <option value="NorthAmerica">North America</option>
              <option value="Oceania">Oceania</option>
              <option value="SouthAmerica">South America</option>
            </select>
          </div>
          <div class="select_child_box">
            <select name="" id="getCountry">
              <option value="">Country/Region *</option>
            </select>
          </div>
        </div>

        <div class="post_input_content ">
          <div class="postcode_input_box">
            <input type="number" name="" placeholder="Search by Postcode" id="postcode_search_field">
          </div>
          <div class="postcode_search_box">
            <button id="postcode_search_button"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
          </div>
        </div>

      </div>

      <div class="search_by_zip_box">
        <button class="repeat_cng" title="Search By Postcode"><i class="fa-solid fa-repeat"></i></button>
      </div>
    </div>
    <div class="show_store_search_result">
      <div class="search_box">
        <input type="text" name="" id="searchInput" placeholder="Search here....">
      </div>
      <div class="search_result_content">
        <table>
          <tbody id="searchTableContent">
            <?php
             global $wpdb;
             $results = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}strfn_all_store_data_save");
          foreach ($results as $result):
        ?>
        <tr>
          <td class="store_content">
            <span>
              <?php echo esc_html($result->store_name); ?>
            </span>
            <ul>
              <li><span><i class="fa-solid fa-location-dot"></i>
                  <?php echo esc_html($result->store_address); ?>
                </span></li>
              <li><span><i class="fa-regular fa-clock"></i>
                  <?php echo esc_html($result->store_open_close); ?>
                </span></li>
              <li><span><i class="fa-solid fa-phone"></i>
                  <?php echo esc_html($result->store_mobile); ?>
                </span></li>
              <li><span><i class="fa-solid fa-envelope"></i>
                  <?php echo esc_html($result->store_email); ?>
                </span></li>
              <li>
                <div class="website_link_div">
                  <a href="<?php echo esc_url($result->store_map); ?>">Direction</a><a
                    href="<?php echo esc_url($result->store_website); ?>">Website</a>
                </div>
              </li>
            </ul>
          </td>
        </tr>
        <?php
      endforeach;
      ?>
          </tbody>
        </table>
      </div>
    </div>
    <div id="overlay">
      <div class="cv-spinner">
        <span class="cspinner"></span>
      </div>
    </div>
    <?php
    $html = ob_get_clean();
    return $html;

  }


  public function get_continent_based_country_callback()
  {
    if (!isset ($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'continent_based_country_nonce'))
    {
      // Nonce verification failed, handle error
      echo 'Nonce verification failed!';
      return;
    }
    $continent = isset ($_REQUEST['continentVal']) ? sanitize_text_field($_REQUEST['continentVal']) : '';

    global $wpdb;
    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $table_name = $wpdb->prefix . 'strfn_all_store_data_save';

    $results = wp_cache_get('store_data_' . $continent);

    if (false === $results)
    {

      // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
      $results = $wpdb->get_results(
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $wpdb->prepare("SELECT * FROM {$table_name} WHERE `store_continent` = %s", $continent)
      );

      // Cache the results for future use
      wp_cache_set('store_data_' . $continent, $results);
    }


    switch ($continent)
    {
      case "Africa":
        $africaCountries = array(
          "null" => "Select a Country",
          "DZ" => "Algeria",
          "AO" => "Angola",
          "BJ" => "Benin",
          "BW" => "Botswana",
          "BF" => "Burkina Faso",
          "BI" => "Burundi",
          "CM" => "Cameroon",
          "CV" => "Cape Verde",
          "CF" => "Central African Republic",
          "TD" => "Chad",
          "KM" => "Comoros",
          "CG" => "Congo",
          "CD" => "Congo, the Democratic Republic of the",
          "DJ" => "Djibouti",
          "EG" => "Egypt",
          "GQ" => "Equatorial Guinea",
          "ER" => "Eritrea",
          "ET" => "Ethiopia",
          "GA" => "Gabon",
          "GM" => "Gambia",
          "GH" => "Ghana",
          "GN" => "Guinea",
          "GW" => "Guinea-Bissau",
          "KE" => "Kenya",
          "LS" => "Lesotho",
          "LR" => "Liberia",
          "LY" => "Libyan Arab Jamahiriya",
          "MG" => "Madagascar",
          "MW" => "Malawi",
          "ML" => "Mali",
          "MR" => "Mauritania",
          "MU" => "Mauritius",
          "YT" => "Mayotte",
          "MA" => "Morocco",
          "MZ" => "Mozambique",
          "NA" => "Namibia",
          "NE" => "Niger",
          "NG" => "Nigeria",
          "RE" => "Reunion",
          "RW" => "Rwanda",
          "SH" => "Saint Helena",
          "ST" => "Sao Tome and Principe",
          "SN" => "Senegal",
          "SC" => "Seychelles",
          "SL" => "Sierra Leone",
          "SO" => "Somalia",
          "ZA" => "South Africa",
          "SS" => "South Sudan",
          "SD" => "Sudan",
          "SZ" => "Swaziland",
          "TZ" => "Tanzania, United Republic of",
          "TG" => "Togo",
          "TN" => "Tunisia",
          "UG" => "Uganda",
          "EH" => "Western Sahara",
          "ZM" => "Zambia",
          "ZW" => "Zimbabwe"
        );
        ob_start();
        foreach ($africaCountries as $code => $country):
          ?>
          <option value="<?php echo esc_attr($code); ?>">
            <?php echo esc_html($country); ?>
          </option>
          <?php
        endforeach;
        $html = ob_get_clean();
        break;
      case "Antarctica":
        ob_start();

        ?>
        <option value="">
          <?php echo esc_html("No Country Found"); ?>
        </option>
        <?php

        $html = ob_get_clean();
        break;
      case "Asia":
        $asiaCountry = array(
          "null" => "Select a Country",
          "AF" => "Afghanistan",
          "AM" => "Armenia",
          "AZ" => "Azerbaijan",
          "BH" => "Bahrain",
          "BD" => "Bangladesh",
          "BT" => "Bhutan",
          "BN" => "Brunei Darussalam",
          "KH" => "Cambodia",
          "CN" => "China",
          "CY" => "Cyprus",
          "GE" => "Georgia",
          "IN" => "India",
          "ID" => "Indonesia",
          "IR" => "Iran, Islamic Republic of",
          "IQ" => "Iraq",
          "IL" => "Israel",
          "JP" => "Japan",
          "JO" => "Jordan",
          "KZ" => "Kazakhstan",
          "KW" => "Kuwait",
          "KG" => "Kyrgyzstan",
          "LA" => "Lao People's Democratic Republic",
          "LB" => "Lebanon",
          "MY" => "Malaysia",
          "MV" => "Maldives",
          "MN" => "Mongolia",
          "MM" => "Myanmar",
          "NP" => "Nepal",
          "KP" => "Korea, Democratic People's Republic of",
          "OM" => "Oman",
          "PK" => "Pakistan",
          "PS" => "Palestinian Territory, Occupied",
          "PH" => "Philippines",
          "QA" => "Qatar",
          "SA" => "Saudi Arabia",
          "SG" => "Singapore",
          "LK" => "Sri Lanka",
          "SY" => "Syrian Arab Republic",
          "TW" => "Taiwan, Province of China",
          "TJ" => "Tajikistan",
          "TH" => "Thailand",
          "TL" => "Timor-Leste",
          "TR" => "Turkey",
          "TM" => "Turkmenistan",
          "AE" => "United Arab Emirates",
          "UZ" => "Uzbekistan",
          "VN" => "Viet Nam",
          "YE" => "Yemen"
        );

        ob_start();
        foreach ($asiaCountry as $code => $country):
          ?>
          <option value="<?php echo esc_attr($code); ?>">
            <?php echo esc_html($country); ?>
          </option>
          <?php
        endforeach;
        $html = ob_get_clean();
        break;
      case "Europe":
        $european_countries = array(
          "null" => "Select a Country",
          "AL" => "Albania",
          "AD" => "Andorra",
          "AM" => "Armenia",
          "AT" => "Austria",
          "AZ" => "Azerbaijan",
          "BY" => "Belarus",
          "BE" => "Belgium",
          "BA" => "Bosnia and Herzegovina",
          "BG" => "Bulgaria",
          "HR" => "Croatia",
          "CY" => "Cyprus",
          "CZ" => "Czech Republic",
          "DK" => "Denmark",
          "EE" => "Estonia",
          "FI" => "Finland",
          "FR" => "France",
          "GE" => "Georgia",
          "DE" => "Germany",
          "GR" => "Greece",
          "HU" => "Hungary",
          "IS" => "Iceland",
          "IE" => "Ireland",
          "IT" => "Italy",
          "LV" => "Latvia",
          "LI" => "Liechtenstein",
          "LT" => "Lithuania",
          "LU" => "Luxembourg",
          "MT" => "Malta",
          "MD" => "Moldova",
          "MC" => "Monaco",
          "ME" => "Montenegro",
          "NL" => "Netherlands",
          "MK" => "North Macedonia",
          "NO" => "Norway",
          "PL" => "Poland",
          "PT" => "Portugal",
          "RO" => "Romania",
          "RU" => "Russia",
          "SM" => "San Marino",
          "RS" => "Serbia",
          "SK" => "Slovakia",
          "SI" => "Slovenia",
          "ES" => "Spain",
          "SE" => "Sweden",
          "CH" => "Switzerland",
          "TR" => "Turkey",
          "UA" => "Ukraine",
          "GB" => "United Kingdom",
          "VA" => "Vatican City",
        );

        ob_start();
        foreach ($european_countries as $code => $country):
          ?>
          <option value="<?php echo esc_attr($code); ?>">
            <?php echo esc_html($country); ?>
          </option>
          <?php
        endforeach;
        $html = ob_get_clean();
        break;
      case "NorthAmerica":
        $north_american_countries = array(
          "null" => "Select a Country",
          "AG" => "Antigua and Barbuda",
          "BS" => "Bahamas",
          "BB" => "Barbados",
          "BZ" => "Belize",
          "CA" => "Canada",
          "CR" => "Costa Rica",
          "CU" => "Cuba",
          "DM" => "Dominica",
          "DO" => "Dominican Republic",
          "SV" => "El Salvador",
          "GD" => "Grenada",
          "GT" => "Guatemala",
          "HT" => "Haiti",
          "HN" => "Honduras",
          "JM" => "Jamaica",
          "MX" => "Mexico",
          "NI" => "Nicaragua",
          "PA" => "Panama",
          "KN" => "Saint Kitts and Nevis",
          "LC" => "Saint Lucia",
          "VC" => "Saint Vincent and the Grenadines",
          "TT" => "Trinidad and Tobago",
          "US" => "United States of America"
        );

        ob_start();
        foreach ($north_american_countries as $code => $country):
          ?>
          <option value="<?php echo esc_attr($code); ?>">
            <?php echo esc_html($country); ?>
          </option>
          <?php
        endforeach;
        $html = ob_get_clean();
        break;
      case "Oceania":
        $oceania_countries = array(
          "null" => "Select a Country",
          "AU" => "Australia",
          "FJ" => "Fiji",
          "KI" => "Kiribati",
          "MH" => "Marshall Islands",
          "FM" => "Micronesia",
          "NR" => "Nauru",
          "NZ" => "New Zealand",
          "PW" => "Palau",
          "PG" => "Papua New Guinea",
          "WS" => "Samoa",
          "SB" => "Solomon Islands",
          "TO" => "Tonga",
          "TV" => "Tuvalu",
          "VU" => "Vanuatu"
        );
        ob_start();
        foreach ($oceania_countries as $code => $country):
          ?>
          <option value="<?php echo esc_attr($code); ?>">
            <?php echo esc_html($country); ?>
          </option>
          <?php
        endforeach;
        $html = ob_get_clean();
        break;
      case "SouthAmerica":
        $south_america_countries = array(
          "null" => "Select a Country",
          "AR" => "Argentina",
          "BO" => "Bolivia",
          "BR" => "Brazil",
          "CL" => "Chile",
          "CO" => "Colombia",
          "EC" => "Ecuador",
          "GY" => "Guyana",
          "PY" => "Paraguay",
          "PE" => "Peru",
          "SR" => "Suriname",
          "UY" => "Uruguay",
          "VE" => "Venezuela"
        );
        ob_start();
        foreach ($south_america_countries as $code => $country):
          ?>
          <option value="<?php echo esc_attr($code); ?>">
            <?php echo esc_html($country); ?>
          </option>
          <?php
        endforeach;
        $html = ob_get_clean();
        break;
      default:

        ob_start();

        ?>
        <option value="">Country/Region *</option>
        <?php

        $html = ob_get_clean();
    }


    ob_start();
    if (!empty ($results)):
      foreach ($results as $result):
        ?>
        <tr>
          <td class="store_content">
            <span>
              <?php echo esc_html($result->store_name); ?>
            </span>
            <ul>
              <li><span><i class="fa-solid fa-location-dot"></i>
                  <?php echo esc_html($result->store_address); ?>
                </span></li>
              <li><span><i class="fa-regular fa-clock"></i>
                  <?php echo esc_html($result->store_open_close); ?>
                </span></li>
              <li><span><i class="fa-solid fa-phone"></i>
                  <?php echo esc_html($result->store_mobile); ?>
                </span></li>
              <li><span><i class="fa-solid fa-envelope"></i>
                  <?php echo esc_html($result->store_email); ?>
                </span></li>
              <li>
                <div class="website_link_div">
                  <a href="<?php echo esc_url($result->store_map); ?>">Direction</a><a
                    href="<?php echo esc_url($result->store_website); ?>">Website</a>
                </div>
              </li>
            </ul>
          </td>
        </tr>
        <?php
      endforeach;
    else:
          global $wpdb;
          $results = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}strfn_all_store_data_save");
          foreach ($results as $result):
        ?>
        <tr>
          <td class="store_content">
            <span>
              <?php echo esc_html($result->store_name); ?>
            </span>
            <ul>
              <li><span><i class="fa-solid fa-location-dot"></i>
                  <?php echo esc_html($result->store_address); ?>
                </span></li>
              <li><span><i class="fa-regular fa-clock"></i>
                  <?php echo esc_html($result->store_open_close); ?>
                </span></li>
              <li><span><i class="fa-solid fa-phone"></i>
                  <?php echo esc_html($result->store_mobile); ?>
                </span></li>
              <li><span><i class="fa-solid fa-envelope"></i>
                  <?php echo esc_html($result->store_email); ?>
                </span></li>
              <li>
                <div class="website_link_div">
                  <a href="<?php echo esc_url($result->store_map); ?>">Direction</a><a
                    href="<?php echo esc_url($result->store_website); ?>">Website</a>
                </div>
              </li>
            </ul>
          </td>
        </tr>
        <?php
      endforeach;
    endif;
    $conHtml = ob_get_clean();


    wp_send_json_success(array("ajaxCon" => $continent, "continentHtml" => $conHtml, "html" => $html));
    wp_die();
  }

  public function get_data_based_country_continent_callback()
  {
    if (!isset ($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'country_continent_based_nonce'))
    {
      echo 'Nonce verification failed!';
      return;
    }
    $continent = isset ($_REQUEST['continentVal']) ? sanitize_text_field($_REQUEST['continentVal']) : '';
    $countryVal = isset ($_REQUEST['countryVal']) ? sanitize_text_field($_REQUEST['countryVal']) : '';

    global $wpdb;
    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $table_name = $wpdb->prefix . 'strfn_all_store_data_save';
    $results = wp_cache_get('store_data_' . $countryVal);
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
    if (false === $results)
    {
      // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
      $results = $wpdb->get_results(
        $wpdb->prepare(
          // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
          "SELECT * FROM {$table_name} WHERE `store_continent` = %s AND `store_country` = %s ",
          $continent,
          $countryVal
        )
      );

      wp_cache_set('store_data_' . $countryVal, $results);
    }

    ob_start();
    if (!empty ($results)):
      foreach ($results as $result):
        ?>
        <tr>
          <td class="store_content">
            <span>
              <?php echo esc_html($result->store_name); ?>
            </span>
            <ul>
              <li><span><i class="fa-solid fa-location-dot"></i>
                  <?php echo esc_html($result->store_address); ?>
                </span></li>
              <li><span><i class="fa-regular fa-clock"></i>
                  <?php echo esc_html($result->store_open_close); ?>
                </span></li>
              <li><span><i class="fa-solid fa-phone"></i>
                  <?php echo esc_html($result->store_mobile); ?>
                </span></li>
              <li><span><i class="fa-solid fa-envelope"></i>
                  <?php echo esc_html($result->store_email); ?>
                </span></li>
              <li>
                <div class="website_link_div">
                  <a href="<?php echo esc_url($result->store_map); ?>">Direction</a><a
                    href="<?php echo esc_url($result->store_website); ?>">Website</a>
                </div>
              </li>
            </ul>
          </td>
        </tr>
        <?php
      endforeach;
    else:
      ?>
      <tr class="default_row">
        <td>No Store Found</td>
      </tr>
      <?php
    endif;
    $html = ob_get_clean();
    wp_send_json_success(array("Con" => $continent, "country" => $countryVal, "html" => $html));
    wp_die();
  }
  public function get_data_based_post_code_callback()
  {
    if (!isset ($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'get_data_based_post_code_nonce'))
    {
      // Nonce verification failed, handle error
      echo 'Nonce verification failed!';
      return;
    }
    $postcode_search_field = isset ($_REQUEST['postcode_search_field']) ? sanitize_text_field($_REQUEST['postcode_search_field']) : '';

    global $wpdb;
    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $table_name = $wpdb->prefix . 'strfn_all_store_data_save';
    $results = wp_cache_get('store_data_' . $postcode_search_field);

    if (false === $results)
    {
      // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
      $results = $wpdb->get_results(
        $wpdb->prepare(
          // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
          "SELECT * FROM {$table_name} WHERE store_postcode =%s ",
          $postcode_search_field
        )
      );

      wp_cache_set('store_data_' . $postcode_search_field, $results);
    }
    ob_start();
    if (!empty ($results)):
      foreach ($results as $result):
        ?>
        <tr>
          <td class="store_content">
            <span>
              <?php echo esc_html($result->store_name); ?>
            </span>
            <ul>
              <li><span><i class="fa-solid fa-location-dot"></i>
                  <?php echo esc_html($result->store_address); ?>
                </span></li>
              <li><span><i class="fa-regular fa-clock"></i>
                  <?php echo esc_html($result->store_open_close); ?>
                </span></li>
              <li><span><i class="fa-solid fa-phone"></i>
                  <?php echo esc_html($result->store_mobile); ?>
                </span></li>
              <li><span><i class="fa-solid fa-envelope"></i>
                  <?php echo esc_html($result->store_email); ?>
                </span></li>
              <li>
                <div class="website_link_div">
                  <a href="<?php echo esc_url($result->store_map); ?>">Direction</a><a
                    href="<?php echo esc_url($result->store_website); ?>">Website</a>
                </div>
              </li>
            </ul>
          </td>
        </tr>
        <?php
      endforeach;
    else:
      ?>
      <tr class="default_row">
        <td>No Store Found</td>
      </tr>
      <?php
    endif;
    $html = ob_get_clean();
    wp_send_json_success(array("postcode_search_field" => $postcode_search_field, "html" => $html, "result" => $results));
    wp_die();
  }
}

new Store_Finder_Shortcode();