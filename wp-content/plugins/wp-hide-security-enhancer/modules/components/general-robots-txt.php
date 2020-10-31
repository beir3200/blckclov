<?php

    if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
    
    class WPH_module_general_robots_txt extends WPH_module_component
        {
            function get_component_title()
                {
                    return "Robots.txt";
                }
                                        
            function get_module_settings()
                {
                    $this->module_settings[]                  =   array(
                                                                    'id'            =>  'disable_robots_txt',
                                                                    'label'         =>  __('Disable admin url within Robots.txt',    'wp-hide-security-enhancer'),
                                                                    'description'   =>  __('Disable any admin url which is being automatically generated by WordPress when called robots.txt.',  'wp-hide-security-enhancer'),
                                                                    
                                                                    'help'          =>  array(
                                                                                                'title'                     =>  __('Help',    'wp-hide-security-enhancer') . ' - ' . __('Disable admin url within Robots.txt',    'wp-hide-security-enhancer'),
                                                                                                'description'               =>  __("The robots.txt file plays a major role in search engine ranking. It blocks search engine bots and helps index and crawl important parts of your site.",    'wp-hide-security-enhancer') .
                                                                                                                                    "<br /><br />" . __("As default the robots.txt also includes an allow clause to admin URL and admin-ajax.php url. Once customized those areas, the new slugs might not want to be show to anyone. Turn this option to Yes removed any reference to new wp-admin and admin-ajax.php.",    'wp-hide-security-enhancer') .
                                                                                                                                    "<br/><br />" . __("Sample robots.txt url:" ,    'wp-hide-security-enhancer') .
                                                                                                                                    "<br /><code>https://-domain-name-/robots.txt</code>",
                                                                                                'option_documentation_url'  =>  'https://www.wp-hide.com/documentation/general-html-robots-txt/'
                                                                                                ),
                                                                    
                                                                    'input_type'    =>  'radio',
                                                                    'options'       =>  array(
                                                                                                'no'        =>  __('No',     'wp-hide-security-enhancer'),
                                                                                                'yes'       =>  __('Yes',    'wp-hide-security-enhancer'),
                                                                                                ),
                                                                    'default_value' =>  'no',
                                                                    
                                                                    'sanitize_type' =>  array('sanitize_title', 'strtolower')
                                                                    
                                                                    ); 
                  
                                                                    
                    return $this->module_settings;   
                }
                
                
                
            function _init_disable_robots_txt($saved_field_data)
                {
                    if(empty($saved_field_data) ||  $saved_field_data   ==  'no')
                        return FALSE;
                        
                    add_action( 'robots_txt', array($this, 'disable_robots_txt' ), 999, 2);
                }
                
            
            function disable_robots_txt( $output, $public )
                {
                                        
                    $search_for     =   '/wp-admin/';
                    
                    $lines  =   preg_split("/\\r\\n|\\r|\\n/", $output);
                    
                    foreach($lines  as  $key    =>  $line)
                        {
                            
                            if(stripos($line, $search_for)  !== FALSE)
                                unset($lines[$key]);
                            
                        }                   
                        
                    $output =   implode(PHP_EOL, $lines);
                        
                    return $output;
                    
                }
                    
         
        }
?>