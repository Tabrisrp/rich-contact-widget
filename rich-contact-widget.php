<?php
/*
Plugin Name: Rich Contact Widget
Plugin URI: http://remyperona.fr/rich-contact-widget/
Description: A simple contact widget enhanced with microdatas & microformats tags
Version: 1.4.6
Author: Rémy Perona
Author URI: http://remyperona.fr
License: GPL2
Text Domain: rich-contact-widget
	Copyright 2012  Rémy Perona  (email : remperona@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
/**
 * Class for vcard creation & saving
 */
class VCF {
    protected $vcard_data;
    protected $name;
    public function __construct( $data ) {
        $this->name = strtolower( remove_accents( trim( str_replace( ' ', '', $data['name'] ) ) ) );
        $this->vcard_data = "BEGIN:VCARD\nVERSION:3.0\nREV:".date("Ymd\THis\Z")."\nFN:".$data['name']."\nTITLE:".$data['activity']."\nADR;WORK:;;".$data['address'].";".$data['city'].";;".$data['postal_code'].";".$data['country']."\nTEL;WORK;VOICE:".$data['phone']."\nEMAIL;WORK;INTERNET:".$data['email']."\nURL;WORK:". home_url() ."\nEND:VCARD";
    }

    public function save() {
        $url = wp_nonce_url('widgets.php?editwidget=rc_widget-2','rich-contact-widget');
        if (false === ($creds = request_filesystem_credentials($url, '', false, false, null) ) ) {
            return; // stop processing here
        }
        if ( ! WP_Filesystem($creds) ) {
            request_filesystem_credentials($url, '', true, false, null);
            return;
        }
        $upload_dir = wp_upload_dir();
        global $wp_filesystem;
        $wp_filesystem->put_contents(
            $upload_dir['basedir'] . '/'. $this->name . '.vcf',
            $this->vcard_data
        );
    }
}

class RP_Geositemap {
    protected $kml_data;
    protected $sitemap_data;
    protected $name;
    protected $coords;
    protected $complete_address;

    public function __construct( $data ) {
        $upload_dir = wp_upload_dir();
        $this->name = strtolower( remove_accents( trim( str_replace( ' ', '', $data['name'] ) ) ) );
        $complete_address = $data['address'] . ' ' . $data['city'] . ' ' . $data['country'];
        $coords = $this->get_coords( $complete_address );
        $this->kml_data = '<?xml version="1.0" encoding="UTF-8"?>
            <kml xmlns="http://www.opengis.net/kml/2.2">
              <Placemark>
                <name>' . $data['name'] . '</name>
                <description>' . $data['activity'] . '</description>
                <Point>
                  <coordinates>' . $coords['lon'] . ',' . $coords['lat'] . '</coordinates>
                </Point>
              </Placemark>
            </kml>';
        
        $this->sitemap_data = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
                        <url>
                           <loc>' . $upload_dir['baseurl'] . '/' . $this->name . '.kml</loc>
                        </url>
                        
                        </urlset>';
    }

    public function save() {
        $upload_dir = wp_upload_dir();
        $url = wp_nonce_url('widgets.php?editwidget=rc_widget-2','rich-contact-widget');
        if (false === ($creds = request_filesystem_credentials($url, '', false, false, null) ) ) {
            return; // stop processing here
        }
        if ( ! WP_Filesystem($creds) ) {
            request_filesystem_credentials($url, '', true, false, null);
            return;
        }
        global $wp_filesystem;
        $wp_filesystem->put_contents(
            $upload_dir['basedir'] . '/' . $this->name . '.kml',
            $this->kml_data
        );
        $wp_filesystem->put_contents(
            $upload_dir['basedir'] . '/rc_geositemap.xml',
            $this->sitemap_data
        );
    }

    private function get_coords( $address ) {
    	$coords=array();
        $base_url="http://maps.googleapis.com/maps/api/geocode/xml?";
        // ajouter &region=FR si ambiguité (lieu de la requete pris par défaut)
        $request_url = $base_url . "address=" . urlencode($address).'&sensor=false';
        $data = wp_remote_get( $request_url );
        $xml = wp_remote_retrieve_body( $data );
        $xml_content = simplexml_load_string( $xml );

        $coords['lat'] = $coords['lon'] = '';
        $coords['status'] = $xml_content->status ;
        if($coords['status']=='OK') {
            $coords['lat'] = $xml_content->result->geometry->location->lat ;
            $coords['lon'] = $xml_content->result->geometry->location->lng ;
        }
        return $coords;
	}

    public function add_to_wpseo_sitemap() {
        $upload_dir = wp_upload_dir();
        return '<sitemap>
        <loc>' . $upload_dir['baseurl'] . '/rc_geositemap.xml</loc>
        <lastmod>1970-01-01T00:00:00+00:00</lastmod>
        </sitemap>';
    }
}

/**
 * Adds RC_Widget widget.
 */
class RC_Widget extends WP_Widget {

	/**
	 * Array containing the keys for each value of the contact fields
	 */
	public function widget_keys() {
		$widget_keys = apply_filters( 'rc_widget_keys', array(
			'title',
			'type',
			'name',
			'activity',
			'address',
			'state',
			'postal_code',
			'city',
			'country',
			'phone',
			'email',
			'map',
			'map_width',
			'map_height',
			'vcf'
			)
		);
		return $widget_keys;
	}

	public function types_options( $types_array, $count, $type ) {
    	$count++;
    	$types_option = '';
    	foreach ( $types_array as $key => $value ) {
        	if ( !is_array( $value ) ) {
            	$types_option .= '<option value="'. $value . '" ' . selected( $type , $value, false ) . '>' . str_repeat( '-', $count ) . ' ' . $value . '</option>';
            } else { 
                $types_option .= '<option value="' . $key . '" ' . selected( $type , $key, false ) . '>' . str_repeat( '-', $count ) . ' ' . $key . '</option>';
                $types_option .= $this->types_options( $value, $count, $type );
            }
        }
    return $types_option;
	}
    
	/**
	 * Register widget with WordPress.
	 */
	public function __construct() {
		parent::__construct(
	 		'rc_widget', // Base ID
			'Rich Contact Widget', // Name
			array( 'description' => __( 'A contact widget enhanced with microdatas & microformats tags', 'rich-contact-widget' ), ) // Args
		);
	}

	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {
		extract( $args );
		$title = apply_filters( 'widget_title', $instance['title'] );

		echo $before_widget;
		if ( ! empty( $title ) )
			echo $before_title . $title . $after_title;
		if ( $instance['type'] == 'Person' ) {
			$activity = 'jobTitle';
			$org = '';
		} else {
			$activity = 'description';
			$org = ' org';
		}
		
		$widget_output = '<ul class="vcard" itemscope itemtype="http://schema.org/'. $instance['type'] . '">';
			if ( !empty( $instance['name'] ) )
				$widget_output .= '<li class="fn ' . $org . '" itemprop="name"><strong>' . $instance['name'] . '</strong></li>';
			if ( !empty( $instance['activity'] ) )
				$widget_output .= '<li itemprop="' . $activity . '">' . $instance['activity'] . '</li>';
			$widget_output .= '<li><div class="adr" itemprop="address" itemscope itemtype="http://schema.org/PostalAddress">';
				if ( !empty( $instance['address'] ) ) {
					$widget_output .= '<span class="street-address" itemprop="streetAddress">' . nl2br( $instance['address'] ) . '</span><br>';
					}
				if ( !empty( $instance['postal_code'] ) || ( get_locale() == 'en_US' && !empty( $instance['state'] ) ) ||!empty( $instance['city'] ) ) {
					if ( get_locale() == 'en_US' ) {
    					if ( !empty( $instance['city'] ) ) {
        					$widget_output .= '<span class="locality" itemprop="addressLocality">' . $instance['city'] . '</span>,&nbsp;';
        				}
        				if ( !empty( $instance['state'] ) ) {
            				$widget_output .= '<span class="region" itemprop="addressRegion">' . $instance['state'] . '</span>&nbsp;';
                        }
                        if ( !empty( $instance['postal_code'] ) ) {
                            $widget_output .= '<span class="postal-code" itemprop="postalCode">' . $instance['postal_code'] . '</span><br>';
	  					}
					}
					else {
					   if ( !empty( $instance['postal_code'] ) ) {
					   	   $widget_output .= '<span class="postal-code" itemprop="postalCode">' . $instance['postal_code'] . '</span>&nbsp;';
					   }
					   if ( !empty( $instance['city'] ) ) {
					   	   $widget_output .= '<span class="locality" itemprop="addressLocality">' . $instance['city'] . '</span><br>';
					   }
					}
				}
				if ( !empty( $instance['country'] ) ) {
					$widget_output .= '<span class="country-name" itemprop="addressCountry">' . $instance['country'] . '</span>';
				}
			$widget_output .= '</div></li>';
			if ( !empty( $instance['phone'] ) ) {
				$widget_output .= '<li class="tel" itemprop="telephone">';

				global $wp_version;
				if ( version_compare( $wp_version, '3.4', '>=' ) && wp_is_mobile() ) {
				        $widget_output .= '<a href="tel:' . $instance['phone'] . '">' . $instance['phone'] . '</a>';
				}
				else
				    $widget_output .= $instance['phone'];
				$widget_output .= '</li>';
			}
			if ( !empty( $instance['email'] ) ) {
				$widget_output .= '<li class="email" itemprop="email"><a href="mailto:' . antispambot($instance['email']) . '">' . $instance['email'] . '</a></li>';
			}

			if ( $instance['vcf'] == 1 ) {
    			$upload_dir = wp_upload_dir();
    			$widget_output .= '<li class="vcf"><a href="' . $upload_dir['baseurl'] . '/' . strtolower( remove_accents( trim( str_replace( ' ', '', $instance['name'] ) ) ) ) . '.vcf">' . __( 'Download vCard', 'rich-contact-widget' ) . '</a></li>';
			}

		$widget_output .= '</ul>';
		if ( $instance['map'] == 1 ) {
            if ( empty( $instance['map_width'] ) ) {
                $instance['map_width'] = 200;
            }

            if ( $instance['map_width'] > 640 ) {
                $instance['map_width'] = 640;
            }

            if ( empty( $instance['map_height'] ) ) {
                $instance['map_height'] = 200;
            }

            if ( $instance['map_height'] > 640 ) {
                $instance['map_height'] = 640;
            }

            if (get_locale() == 'en_US')
		      $map_adress = $instance['address'] . ' ' . $instance['city'] . ' ' . $instance['state'] . ' ' . $instance['postal_code'] . ' ' . $instance['country'];
		  else
		      $map_adress = $instance['address'] . ' ' . $instance['postal_code'] . ' ' . $instance['city'] . ' ' . $instance['country'];

		$encoded_map_adress = str_replace( ' ', '+', $map_adress );
            
    		$widget_output .= '<a href="https://google.com/maps/place/'. $encoded_map_adress . '/" target="_blank"><img src="http://maps.googleapis.com/maps/api/staticmap?center=' . $encoded_map_adress . '&amp;zoom=15&amp;size=' . $instance['map_width'] . 'x' . $instance['map_height'] . '&amp;sensor=false&amp;markers=' . $encoded_map_adress . '" alt="' . __('Map for', 'rich_contact-widget') . ' ' . $map_adress . '" width="' . $instance['map_width'] . '" height="' . $instance['map_height'] . '"></a>';
        }
		$widget_output = apply_filters( 'rc_widget_output', $widget_output, $instance );
		echo $widget_output;
		echo $after_widget;
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		foreach ( $this->widget_keys() as $key=>$value ) {
			if ( $old_instance[ $value ] != $new_instance[ $value ] || !array_key_exists($value, $old_instance) ) {
				$new_instance[ $value ] = strip_tags( $new_instance[$value] );
			}
		}
		if ( $new_instance['vcf'] == 1 ) {
		  $vcf = new VCF( $new_instance );
		  $vcf->save();
		  }

        $geositemap = new RP_Geositemap( $new_instance );
        $geositemap->save();
		return $new_instance;
	}

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {
		foreach ( $this->widget_keys() as $key=>$value ) {
			if ( !array_key_exists( $value, $instance ) && $value == 'title' ) {
				${$value} = __( 'Contact', 'rich-contact-widget' );
			} elseif ( !array_key_exists( $value, $instance ) ) {
				${$value} = '';
			} else {
				${$value} = $instance[ $value ];
			}
		}

		$widget_form_output = '<p>
		<label for="' . $this->get_field_id( 'title' ) . '">' . __( 'Title :' , 'rich-contact-widget') . '</label> 
		<input class="widefat" id="' . $this->get_field_id( 'title' ) . '" name="' . $this->get_field_name( 'title' ) . '" type="text" value="' . esc_attr( $title ) . '" />
		</p>';
		$widget_form_output .= '<p>
			<select name="' . $this->get_field_name( 'type' ) . '">
			 <option value="">' . __( 'Choose a type', 'rich-contact-widget') . '</option>';
			 $types = apply_filters( 'rc_schema_types', array(
	       'Person',
	       'Corporation',
	       'EducationalOrganization' => array(
	           'CollegeOrUniversity',
	           'ElementarySchool',
	           'HighSchool',
	           'MiddleSchool',
	           'PreSchool',
	           'School'
	       ),
	       'GovernmentOrganization',
	       'LocalBusiness' => array(
	           'AnimalShelter',
	           'AutomotiveBusiness' => array(
	               'AutoBodyShop',
	               'AutoDealer',
	               'AutoPartsStore',
	               'AutoRental',
	               'AutoRepair',
	               'AutoWash',
	               'GasStation',
	               'MotorcycleDealer',
	               'MotorcycleRepair'
	           ),
	           'ChildCare',
	           'DryCleaningOrLaundry',
	           'EmergencyService' => array(
	               'FireStation',
	               'Hospital',
	               'PoliceStation'
	           ),
	           'EmploymentAgency',
	           'EntertainmentBusiness' => array(
	               'AdultEntertainment',
	               'AmusementPark',
	               'ArtGallery',
	               'Casino',
	               'ComedyClub',
	               'MovieTheater',
	               'NightClub',
	           ),
	           'FinancialService' => array(
	               'AccountingService',
	               'AutomatedTeller',
	               'BankOrCreditUnion',
	               'InsuranceAgency'
	           ),
	           'FoodEstablishment' => array(
	               'Bakery',
	               'BarOrPub',
	               'Brewery',
	               'CafeOrCoffeeShop',
	               'FastFoodRestaurant',
	               'IceCreamShop',
	               'Restaurant',
	               'Winery'
	           ),
	           'GovernmentOffice' => array(
	               'PostOffice'
	           ),
	           'HealthAndBeautyBusiness' => array(
	               'BeautySalon',
	               'DaySpa',
	               'HairSalon',
	               'HealthClub',
	               'NailSalon',
	               'TatooParlor'
	           ),
	           'HomeAndConstructionBusiness' => array(
	               'Electrician',
	               'GeneralContractor',
	               'HVACBusiness',
	               'HousePainter',
	               'LockSmith',
	               'MovingCompany',
	               'Plumber',
	               'RoofingContractor'
	           ),
	           'InternetCafe',
	           'Library',
	           'LodgingBusiness' => array(
	               'BedAndBreakfast',
	               'Hostel',
	               'Hotel',
	               'Motel'
	           ),
	           'MedicalOrganization' => array(
	               'Dentist',
	               'DiagnosticLab',
	               'Hospital',
	               'MedicalClinic',
	               'Optician',
	               'Pharmacy',
	               'Physician',
	               'VeterinaryCare'
	           ),
	           'ProfessionalService' => array(
	               'AccountingService',
	               'Attorney',
	               'Dentist',
	               'Electrician',
	               'GeneralContractor',
	               'HousePainter',
	               'Locksmith',
	               'Notary',
	               'Plumber',
	               'RoofingContractor'
	           ),
	           'RadioStation',
	           'RealEstateAgent',
	           'RecyclingCenter',
	           'SelfStorage',
	           'ShoppingCenter',
	           'SportsActivityLocation' => array(
	               'BowlingAlley',
	               'ExerciseGym',
	               'GolfCourse',
	               'HealthClub',
	               'PublicSwimmingPool',
	               'SkiResort',
	               'SportsClub',
	               'StadiumOrArena',
	               'TennisComplex'
	           ),
	           'Store' => array(
	               'AutoPartsStore',
	               'BikeStore',
	               'BookStore',
	               'ClothingStore',
	               'ComputerStore',
	               'ConvenienceStore',
	               'DepartmentStore',
	               'ElectronicsStore',
	               'Florist',
	               'FurnitureStore',
	               'GardenStore',
	               'GroceryStore',
	               'HardwareStore',
	               'HobbyShop',
	               'HomeGoodsStore',
	               'JewelryStore',
	               'LiquorStore',
	               'MensClothingStore',
	               'MobilePhoneStore',
	               'MovieRentalStore',
	               'MusicStore',
	               'OfficeEquipmentStore',
	               'OutletStore',
	               'PawnShop',
	               'PetStore',
	               'ShoeStore',
	               'SportingGoodsStore',
	               'TireShop',
	               'ToyStore',
	               'WholesaleStore'
	           ),
	           'TelevisionStation',
	           'TouristInformationCenter',
	           'TravelAgency'
	       ),
	       'NGO',
	       'PerformingGroup' => array(
	           'DanceGroup',
	           'MusicGroup',
	           'TheaterGroup'
	       ),
	       'SportsTeam'
	       )
	   );
	   $widget_form_output .= $this->types_options( $types, -1, $type );
        $widget_form_output .= '</select>
		</p>';
		$widget_form_output .= '<p>
			<label for="' . $this->get_field_id( 'name' ) . '">' . __( 'Company name/Your name :', 'rich-contact-widget' ) . '</label>
			<input class="widefat" id="' . $this->get_field_id( 'name' ). '" name="' . $this->get_field_name( 'name' ) . '" type="text" value="' . esc_attr( $name ) . '" />
		</p>';
		$widget_form_output .= '<p>
			<label for="' . $this->get_field_id('activity') . '">' . __('Activity/Job :', 'rich-contact-widget') . '</label>
			<input class="widefat" id="' . $this->get_field_id('activity') . '" name="' . $this->get_field_name('activity') . '" type="text" value="' . esc_attr( $activity ) . '" />
		</p>';
		$widget_form_output .= '<p>
			<label for="' . $this->get_field_id( 'address' ) . '">' . __( 'Company address :', 'rich-contact-widget' ) . '</label>
			<textarea class="widefat" id="' . $this->get_field_id( 'address' ) . '" name="' . $this->get_field_name( 'address' ) . '">' . esc_textarea( $address ) . '</textarea>
		</p>';
		$widget_form_output .= '<p>
			<label for="' . $this->get_field_id( 'postal_code' ) . '">' . __( 'Postal/ZIP code :', 'rich-contact-widget' ) . '</label>
			<input class="widefat" id="' . $this->get_field_id( 'postal_code' ) . '" name="' . $this->get_field_name( 'postal_code' ) . '" type="text" value="' . esc_attr( $postal_code ) . '" />
		</p>';
		$widget_form_output .= '<p>
			<label for="' . $this->get_field_id( 'city' ) . '">' . __( 'City :', 'rich-contact-widget' ) . '</label>
			<input class="widefat" id="' . $this->get_field_id( 'city' ) . '" name="' . $this->get_field_name( 'city' ) . '" type="text" value="' .  esc_attr( $city ) . '" />
		</p>';
		if ( get_locale() == 'en_US' ) {
    		$widget_form_output .= '<p>
    		  <label for="' . $this->get_field_id( 'state' ) . '">' . __( 'State :', 'rich-contact-widget' ) . '</label>
    		  <input class="widefat" id="' . $this->get_field_id( 'state' ) . '" name="' . $this->get_field_name( 'state' ) . '" type="text" value="' . esc_attr( $state ) . '" />
    		  </p>';
 		}
		$widget_form_output .= '<p>
			<label for="' . $this->get_field_id( 'country' ) . '">' . __( 'Country :', 'rich-contact-widget' ) . '</label>
			<input class="widefat" id="' . $this->get_field_id( 'country' ) . '" name="' . $this->get_field_name( 'country' ) . '" type="text" value="' . esc_attr( $country ) . '" />
		</p>';
		$widget_form_output .= '<p>
			<label for="' . $this->get_field_id( 'phone' ) . '">' . __( 'Phone number :', 'rich-contact-widget' ) . '</label>
			<input class="widefat" id="' . $this->get_field_id( 'phone' ) . '" name="' . $this->get_field_name( 'phone' ) . '" type="text" value="' . esc_attr( $phone ) . '" />
		</p>';
		$widget_form_output .= '<p>
			<label for="' . $this->get_field_id( 'email' ) . '">' . __( 'Email address :', 'rich-contact-widget' ) . '</label>
			<input class="widefat" id="' . $this->get_field_id( 'email' ) . '" name="' . $this->get_field_name( 'email' ) . '" type="text" value="' . esc_attr( $email ) . '" />
		</p>';
		$widget_form_output .= '<p>
			<label for="' . $this->get_field_id( 'map' ) . '">' . __( 'Show image map :', 'rich-contact-widget' ) . '</label>
			<select name="' . $this->get_field_name( 'map' ) . '" id="' . $this->get_field_id( 'map' ) . '">
			 <option value="1" ' . selected( $map, 1, false ) . '>' . __('Yes', 'rich-contact-widget') . '</option>
			 <option value="0" ' . selected( $map, 0, false ) . '>' . __('No', 'rich-contact-widget') . '</option>
			 </select>
		</p>';
		$widget_form_output .= '<p>
			<label for="' . $this->get_field_id( 'map_width' ) . '">' . __( 'Image map width (max 640px) :', 'rich-contact-widget' ) . '</label>
			<input class="widefat" id="' . $this->get_field_id( 'map_width' ) . '" name="' . $this->get_field_name( 'map_width' ) . '" type="text" value="' . esc_attr( $map_width ) . '" />
		</p>';
		$widget_form_output .= '<p>
			<label for="' . $this->get_field_id( 'map_height' ) . '">' . __( 'Image map height (max 640px) :', 'rich-contact-widget' ) . '</label>
			<input class="widefat" id="' . $this->get_field_id( 'map_height' ) . '" name="' . $this->get_field_name( 'map_height' ) . '" type="text" value="' . esc_attr( $map_height ) . '" />
		</p>';
		$widget_form_output .= '<p>
			<label for="' . $this->get_field_id( 'vcf' ) . '">' . __( 'Show vCard download link :', 'rich-contact-widget' ) . '</label>
			<select name="' . $this->get_field_name( 'vcf' ) . '" id="' . $this->get_field_id( 'vcf' ) . '">
			 <option value="1" ' . selected( $vcf, 1, false ) . '>' . __('Yes', 'rich-contact-widget') . '</option>
			 <option value="0" ' . selected( $vcf, 0, false ) . '>' . __('No', 'rich-contact-widget') . '</option>
			 </select>
		</p>';
		$widget_form_output = apply_filters( 'rc_widget_form_output', $widget_form_output, $instance );
		echo $widget_form_output;
	}

} // class RC_Widget

// register RC_Widget widget

function rcw_register_widget() {
	register_widget('RC_Widget');
}

// init RC_Widget widget
add_action( 'widgets_init', 'rcw_register_widget' );
add_filter( 'wpseo_sitemap_index', array( 'RP_Geositemap', 'add_to_wpseo_sitemap' ) );

// Loading languages for i18n
load_plugin_textdomain('rich-contact-widget', false, basename( dirname( __FILE__ ) ) . '/languages' );