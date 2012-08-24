<?php
class BetterSEO extends Plugin
{
	/**
	 * Add all the SEO related tags to the theme header
	 * 
	 * TODO: should this actually be called action_template_header() ?
	 */
	public function theme_header( $theme )
	{
		$this->theme = $theme;	// We need this so we can access it in our functions
		return $this->get_betterseo;
	}
	
	/**
	 * Taken from the metaseo plugin
	 * 
	 * This filter is called before the display of any page, so it is used
	 * to make any final changes to the output before it is sent to the browser
	 *
	 * @param $buffer string the page being sent to the browser
	 * @return  string the modified page
	 */
	public function filter_final_output( $buffer )
	{
		$seo_title = $this->get_title();
		if ( strlen( $seo_title ) ) {
			if ( strpos( $buffer, '<title>' ) !== false ) {
				$buffer = preg_replace( "%<title\b[^>]*>(.*?)</title>%is", "<title>{$seo_title}</title>", $buffer );
			}
			else {
				$buffer = preg_replace( "%</head>%is", "<title>{$seo_title}</title>\n</head>", $buffer );
			}
		}
		return $buffer;
	}
		
	/* -------:[ WORKER FUNCTIONS ]:------- */
		
	/**
	 * Grab the post/page title
	 */
	public function get_title()
	{
		$matched_rule = URL::get_matched_rule();
		if ( is_object( $matched_rule ) ) {
            $rule = $matched_rule->name;
			switch( $rule ) {
                case 'display_entry':
                    $title = $post->title . $sep . Options::get( 'title' );
                    break;
                case 'display_entries':
                    $title = Options::get( 'title' ).' '.$sep.' '.Options::get( 'tagline' );
                    $title .= ( $page > 1 ) ? $sep .'Page '.$page : '';
                    break;
                case 'display_page':
                    $title = $post->title . $sep . Options::get( 'title' );
                    break;
                case 'display_entries_by_tag':
                    $title = 'Posts tagged with: '.$tag . $sep . Options::get( 'title' );
                    break;
                case 'display_home':
                    $title = Options::get( 'title' ) . $sep . Options::get( 'tagline' );
                    break;
                case 'display_search':
                    $title = ( $_GET['q'] ) ? 'Search results for: '.$_GET['q'] : 'Looking for Something?' . $sep . Options::get( 'title' );
                    break;
                case 'display_404':
                    $title = 'Page Not Found' . $sep . Options::get( 'title' );
                    break;
				case 'display_entries_by_date':
					$month_names = array( 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December' );
					$title = 'Posts for '.$month_names[$month-1].' '.$year.' '.$sep.' Page '.$page;
					break;
                default:
                    $title = $post->title . $sep . Options::get( 'title' );
            }
			return $title;
		}
		
	}
	
	/**
	 * Return all of the necessary headers in a single function call.
	 * 
	 * This returns the necessary HTML header code for:
	 * 
	 * - description
	 * - keywords
	 * - imgsrc  (used by Twitter/Facebook/Google+ for the image in linked posts)
	 * - robots
	 */
	public function get_betterseo()
	{
		// Default options - we only overwrite these if the defaults are not acceptable
		$sep = " - ";	// TODO: Make this configurable
		// About is taken from the About setting in later revs of Habari.
        $description = Options::get( 'about' );
		// Get tags from theme config option.
        $tags = Options::get( __CLASS__ . '__site_tags' );
        $title = '';
        $robots = '';
		// Set this to the apple-touch-icon image, else set it to the first image in the post.
		$image_src = Site::get_url( 'theme' ) . '/img/apple-touch-icon.png';
		
		$matched_rule = URL::get_matched_rule();
		if ( is_object( $matched_rule ) ) {
            $rule = $matched_rule->name;
            switch( $rule ) {
                case 'display_entry':
                    $description = ( self::truncate( $post->content ) != '' ) ? self::truncate( $post->content ) : $post->title;
                    $tags = implode( ', ', (array)$post->tags );
					$imgsrc = $theme->find_first_image( $post->content );
					if ( !is_null( $imgsrc ) ) {
						$image_src = $imgsrc;
					}
                    break;
                case 'display_entries':
                    $robots = 'noindex,follow';
                    break;
                case 'display_page':
                    $description = ( self::truncate( $post->content ) != '' ) ? self::truncate( $post->content ) : $post->title;
                    $tags = implode( ', ', (array)$post->tags );
                    if ( $post->title == 'Archives' ) {
                        $robots = 'noindex,follow';
                    }
                    break;
                case 'display_entries_by_tag':
                    $tags = $tag;
                    $robots = 'noindex,follow';
                    break;
                case 'display_home':
                    break;
                case 'display_search':
                    $robots = 'noindex,follow';
                    break;
                case 'display_404':
                    $description = 'Oooops.  Looks like something has gone AWOL';
                    $robots = 'noindex,follow';
                    break;
				case 'display_entries_by_date':
					$month_names = array( 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December' );
					$description = 'Posts for '.$month_names[$month-1].' '.$year . $sep . 'Page '.$page;
					$robots = 'noindex,follow';
					break;
                default:
                    $description = ( self::truncate( $post->content ) != '' ) ? self::truncate( $post->content ) : $post->title;
                    $tags = ( count( $post->tags ) > 0 ) ? implode( ', ', (array)$post->tags ) : '';
            }
			$out = '<meta name="description" content="' . $description .'">';
			if ( $tags != '' ) { 
				$out .= '<meta name="keywords" content="' . $tags . '">';
			}
			if ( $robots ) {
				$out .= '<meta name="robots" content="' . $robots .'">';
			}
			$out .= '<link rel="image_src" href="' . $image_src .'">';
			
			return $out;
        }
	}
	
	
	/* -------:[ HELPER FUNCTIONS ]:------- */
	
	
	/**
     * Truncate text passed to this function to the specified number of chars - 200 by default
     */
     public static function truncate( $string, $len = 200 )
     {
        $desc = Utils::truncate( strip_tags( $string ), $len, false );
        $desc = str_replace( array( "\r\n", "\n", "   " ), ' ', $desc );
        $desc = htmlspecialchars( strip_tags( $desc ), ENT_COMPAT, 'UTF-8' );
        $desc = strip_tags( trim( $desc ) );
        return $desc;
     }
	
}

?>