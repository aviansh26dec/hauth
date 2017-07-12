<?php if ( !defined( 'BASEPATH' ) ) exit( 'No direct script access allowed' );

class User extends Front_Controller {

	public function index() {
		$data = array();

		$this->load->view( 'users/main', $data );
	}

	public function login() {
		$data = array( 'errors' => '', 'redirect' => '', 'success' => '' );
		$this->footer .= '<script src="'.$this->load->theme_js('login_reg').'" ></script>';
		$this->load->helper('cookie');
		$min_age = 18;

		if( $this->input->post( 'reg_form' ) ) {
			$error = '';
			if( $this->user_model->reg_log_check( $this->input->post( 'log' ) ) )
			{
				$error .= '<p class="bg-danger">This username is already taken, please select another.</p>';
			}

			if( $this->user_model->reg_email_check( $this->input->post( 'email' ) ) )
			{
				$error .= '<p class="bg-danger">This email is already taken, please select another.</p>';
			}

			if( $this->input->post( 'pass' ) != $this->input->post( 'con_pass' ) )
			{
				$error .= '<p class="bg-danger">The passwords you entered do not match.</p>';
			}

			$dob = $this->input->post( 'dob' );

			if( mktime(0, 0, 0, $dob['month'], $dob['day'], $dob['year'] ) > mktime(0, 0, 0, date('m'), date('j'), ( date('Y') - $min_age ) ) ) {

				$error .= "<p class='bg-danger'>You must be over $min_age years old to register</p>";
			}

			if( strlen( $error ) ) {
				$data['errors'] = $error;
			}
			else {
				$code = md5( $this->input->post( 'email' ) );

				$this->user_model->register_user( $this->input->post(), $code );

				$link = site_url( 'user/confirm/' . $code );
				$this->xherb_email->set('to_email', $this->input->post( 'email' ) );
				$this->xherb_email->set('subject', 'Confirm your Email' );
				$this->xherb_email->set('message', "<p>Welcome to Xherb!<p>
					<p>Please follow the <a href='{$link}'>this link</a> to confirm your email address.</p>
					<p>Or copy and paste this URL into your browser: $link</p>");

				$this->xherb_email->send();


				$data['redirect'] = $this->input->post( 'redirect_link' ) ? $this->input->post( 'redirect_link' ) : site_url( 'user/' . $this->input->post('log') );
			}

			echo json_encode( $data );
			die();
		}
		else if( $this->input->post( 'login_form' ) ) {
			if( $user = $this->user_model->user_login( $this->input->post( 'log' ), $this->input->post( 'pass' ) ) ) {
				$data['redirect'] = $this->input->post( 'redirect_link' ) ? $this->input->post( 'redirect_link' ) : site_url( 'user/' . $user->user_log );
			}
			else {
				$data['errors'] = '<p class="bg-danger">There was an issue with your login details, please confirm your details and try again</p>';
			}

			echo json_encode( $data );
			die();
		}
		else if( $this->input->post( 'request_form' ) ) {
			$user = $this->user_model->get_user_by( 'user_email', $this->input->post( 'email' ) );

			if( $user->ID == -1 ) {
				$data['errors'] = '<p class="bg-danger">We could not find an account with that email address.</p>';
			}
			else {
				$salted = $this->user_model->salt_password( $this->input->post( 'email' ) . date( "Y-m-d H:i:s" ) );
				$this->user_model->set_reset_code( $user->ID, $salted );
				$link = site_url( '?reset=' . $salted );

				$this->xherb_email->set('to_email', $this->input->post( 'email' ) );
				$this->xherb_email->set('subject', 'Account Details Request' );
				$this->xherb_email->set('message', "<p>Hello {$user->user_log},<p>
					<p>You or someone else has requested your account details</p>
					<p>Your user name is: {$user->user_log}</p>
					<p>Your password can be reset by following <a href='{$link}'>this link</a><p>
					<p>Or copy and paste this URL into your browser: $link</p>");

				$this->xherb_email->send();

				$data['errors'] = '<p class="bg-success">An email has been sent to this email address with the account details.</p>';
			}

			echo json_encode( $data );
			die();
		}
		else if ( $this->input->post( 'reset_form' ) ) {

			if ( $this->user_model->reset_password( $this->input->post( 'pass' ), $this->input->post( 'reset_code' ) ) ) {

				$data['success'] = '<p class="bg-success">Password successfully changed, please use the login link to access your account</a>' . $this->input->post( 'reg_pass' );
			}
			else {
				$data['errors'] = '<p class="bg-danger">We could not find the account associated with this reset code!</a>';
			}

			echo json_encode( $data );
			die();
		}
		else {
			show_404();
		}
	}

	public function hauth( $provider ) {

		log_message('debug', "controllers.HAuth.login($provider) called");

		try
		{
			log_message('debug', 'controllers.HAuth.login: loading HybridAuthLib');
			$this->load->library('HybridAuthLib');

			if ($this->hybridauthlib->providerEnabled($provider))
			{
				log_message('debug', "controllers.HAuth.login: service $provider enabled, trying to authenticate.");
				$service = $this->hybridauthlib->authenticate($provider);

				if ($service->isUserConnected())
				{
					log_message('debug', 'controller.HAuth.login: user authenticated.');

					$user_profile = $service->getUserProfile();

					log_message('info', 'controllers.HAuth.login: user profile:'.PHP_EOL.print_r($user_profile, TRUE));

					$data['user_profile'] = $user_profile;

					$this->load->view('hauth/done',$data);
				}
				else // Cannot authenticate user
				{
					show_error('Cannot authenticate user');
				}
			}
			else // This service is not enabled.
			{
				log_message('error', 'controllers.HAuth.login: This provider is not enabled ('.$provider.')');
				show_404($_SERVER['REQUEST_URI']);
			}
		}
		catch(Exception $e)
		{
			$error = 'Unexpected error';
			switch($e->getCode())
			{
				case 0 : $error = 'Unspecified error.'; break;
				case 1 : $error = 'Hybriauth configuration error.'; break;
				case 2 : $error = 'Provider not properly configured.'; break;
				case 3 : $error = 'Unknown or disabled provider.'; break;
				case 4 : $error = 'Missing provider application credentials.'; break;
				case 5 : log_message('debug', 'controllers.HAuth.login: Authentification failed. The user has canceled the authentication or the provider refused the connection.');
				         //redirect();
				         if (isset($service))
				         {
				         	log_message('debug', 'controllers.HAuth.login: logging out from service.');
				         	$service->logout();
				         }
				         show_error('User has cancelled the authentication or the provider refused the connection.');
				         break;
				case 6 : $error = 'User profile request failed. Most likely the user is not connected to the provider and he should to authenticate again.';
				         break;
				case 7 : $error = 'User not connected to the provider.';
				         break;
			}

			if (isset($service))
			{
				$service->logout();
			}

			log_message('error', 'controllers.HAuth.login: '.$error);
			show_error('Error authenticating user.');
		}
	}

	public function endpoint() {
		
		log_message('debug', 'controllers.HAuth.endpoint called.');
		log_message('info', 'controllers.HAuth.endpoint: $_REQUEST: '.print_r($_REQUEST, TRUE));

		if ($_SERVER['REQUEST_METHOD'] === 'GET')
		{
			log_message('debug', 'controllers.HAuth.endpoint: the request method is GET, copying REQUEST array into GET array.');
			$_GET = $_REQUEST;
		}

		log_message('debug', 'controllers.HAuth.endpoint: loading the original HybridAuth endpoint script.');
		require_once APPPATH.'/third_party/hybridauth/index.php';
		
	}

	public function confirm( $code ) {

		$confirm = $this->user_model->confirm_code( $code );

		if ( $confirm['affected'] == 1 ) {

			$data['message'] = '<p class="bg-success">Your Email address has been confirmed!</p>';
		}
		else if ( $confirm['affected'] == 0 && $confirm['data']->confirmed == 'Y' ) {

			$data['message'] = '<p class="bg-danger">This Email address has already been confirmed!</p>';
		}
		else {

			$data['message'] = '<p class="bg-danger">There was an error with the code, please try again.</p>';
		}

		$this->load->view( 'users/confirm', $data );
	}

	public function logout() {
		$this->load->library('HybridAuthLib');

		$this->hybridauthlib->logoutAllProviders();
		$this->user_model->user_logout();

		$this->session->set_flashdata( 'site', 'You are now logged out.' );
		redirect( '' );
	}

	public function profile( $username ) {
		$user_id = $this->user_model->id_from_name( $username );

		$this->footer = '<script src="' . $this->load->theme_js('jquery.timeago') . '"></script>';
		$this->footer .= "<script>jQuery('time.timeago').timeago();</script>";

		if( is_profile() ) {

			$this->header = "<link rel=\"stylesheet\" href=\"" . $this->load->theme_css('dropzone'). "\" type=\"text/css\" media=\"all\" />\n";

			$this->footer .= '<script src="' . $this->load->theme_js('underscore') . '"></script>';
			$this->footer .= '<script src="' . $this->load->theme_js('jquery.stepify') . '"></script>';
			$this->footer .= '<script src="' . $this->load->theme_js('profile') . '"></script>';
			$this->footer .= "<script src=\"" . $this->load->theme_js('uploader/jquery.iframe-transport'). "\" ></script>\n";
			$this->footer .= "<script src=\"" . $this->load->theme_js('uploader/jquery.fileupload'). "\" ></script>\n";
			$this->footer .= "<script src=\"" . $this->load->theme_js('uploader/jquery.fileupload-process'). "\" ></script>\n";
			$this->footer .= "<script src=\"" . $this->load->theme_js('uploader/jquery.fileupload-image'). "\" ></script>\n";
		}

		// rating
			$this->header .= "<link rel=\"stylesheet\" href=\"" . $this->load->theme_css('rating/bars-1to10'). "\" type=\"text/css\" media=\"all\" />\n";
			$this->header .= "<link rel=\"stylesheet\" href=\"" . $this->load->theme_css('rating/bars-movie'). "\" type=\"text/css\" media=\"all\" />\n";
			$this->header .= "<link rel=\"stylesheet\" href=\"" . $this->load->theme_css('rating/bars-square'). "\" type=\"text/css\" media=\"all\" />\n";
			$this->header .= "<link rel=\"stylesheet\" href=\"" . $this->load->theme_css('rating/bars-pill'). "\" type=\"text/css\" media=\"all\" />\n";
			$this->header .= "<link rel=\"stylesheet\" href=\"" . $this->load->theme_css('rating/bars-reversed'). "\" type=\"text/css\" media=\"all\" />\n";
			$this->header .= "<link rel=\"stylesheet\" href=\"" . $this->load->theme_css('rating/bars-horizontal'). "\" type=\"text/css\" media=\"all\" />\n";
			
			$this->footer .= '<script src="' . $this->load->theme_js('rating/jquery.barrating') . '"></script>';
			$this->footer .= '<script src="' . $this->load->theme_js('rating/rating') . '"></script>';
			$this->footer .= "<script src=\"" . $this->load->theme_js('strains'). "\" ></script>\n";
			$this->footer .= '<script src="' . $this->load->theme_js('jquery_add_more') . '"></script>';
		// rating close

        $sess = unserialize(base64_decode($_SESSION['_xherb_user'])); 
        if($sess['utype']=='user'){
            $data = $this->user_model->get_user( $sess['user_log'] );
        }else{
        	$data = $this->user_model->get_user( $username );
        }
		

		$data->gallery = $this->user_model->get_gallery( $user_id, false );
		$data->listings = $this->listing_model->get_by_user( $user_id );

		$data->journal = $this->strains_model->get_top_three_journal($user_id);

		if ( strlen( $data->meta['favorite_listing'] ) )
			$data->favorites = $this->listing_model->get_by_ids( explode(',', $data->meta['favorite_listing'] ) );

		$this->load->view( 'users/profile', $data );
	}

	function listing( $id ) {

		$check = $this->listing_model->get_listing( $id );
		$this->session->set_userdata( 'active_listing', $id );

		if ( $check->user_id != $this->user->ID ) {

			show_404();
			exit;
		}
        
		$this->header = "<link rel=\"stylesheet\" href=\"" . $this->load->theme_css('dropzone'). "\" type=\"text/css\" media=\"all\" />\n";

		$this->header .= '<script src="' . $this->load->theme_js('Chart.min') . '"></script>';
		$this->footer = '<script src="' . $this->load->theme_js('profile') . '"></script>';
		
		$this->header .= "<link rel=\"stylesheet\" href=\"" . $this->load->theme_css('dropzone'). "\" type=\"text/css\" media=\"all\" />\n";

		$this->footer .= '<script src="' . $this->load->theme_js('underscore') . '"></script>';
		$this->footer .= '<script src="' . $this->load->theme_js('jquery.stepify') . '"></script>';
		$this->footer .= "<script src=\"" . $this->load->theme_js('uploader/jquery.iframe-transport'). "\" ></script>\n";
		$this->footer .= "<script src=\"" . $this->load->theme_js('uploader/jquery.fileupload'). "\" ></script>\n";
		$this->footer .= "<script src=\"" . $this->load->theme_js('uploader/jquery.fileupload-process'). "\" ></script>\n";
		$this->footer .= "<script src=\"" . $this->load->theme_js('uploader/jquery.fileupload-image'). "\" ></script>\n";

		$this->footer .= "<script>\n
		Dropzone.options.listingDropzone = {
			uploadBase: '" . site_url( '/assets/listing/' . $id ) . "/',
			acceptedFiles: '.jpg,.jpeg,.png,.gif,.tif',
			success: function(file, response) {
				setTimeout( function() {
					$('.gallery-upload .dz-preview').each(function() {

						var src = $(this).find('.dz-image img').attr('src'),
							li = $('<li>'),
							a = $('<a />'),
							img = $('<img />');

							img.attr({src : src, width : '78px', height : '81px' }).addClass('gallery-thumbnail round-all');
							li.html( a.attr({href : src, rel : 'prettyPhoto[listing_gallery]'}).html(img) );

						$(this).fadeOut(500, function() {
							$('.image-gallery-clip ul').prepend( li );
							$(this).remove();
						})
					});
				}, 1000);
			},
		}\n
		var randomScalingFactor = function(){ return Math.round(Math.random()*1000)};
		var lineChartData = {
			labels : ['3am','6am','9am','12pm', '" . date('g:i a') . "'],
			datasets : [
				{
					label: 'My First dataset',
					fillColor : 'rgba(12, 80, 89, 0.2)',
					strokeColor : 'rgba(56, 179, 62, 1)',
					pointColor : 'rgba(12, 80, 89, 1)',
					pointStrokeColor : '#fff',
					pointHighlightFill : '#fff',
					pointHighlightStroke : 'rgba(12, 80, 89, 1)',
					data : [randomScalingFactor(),randomScalingFactor(),randomScalingFactor(),randomScalingFactor(),randomScalingFactor(),randomScalingFactor(),randomScalingFactor()]
				}
			]
		}
		var lineChartData2 = {
			labels : ['3am','6am','9am','12pm', '" . date('g:i a') . "'],
			datasets : [
				{
					label: 'My First dataset',
					fillColor : 'rgba(12, 80, 89, 0.2)',
					strokeColor : 'rgba(56, 179, 62, 1)',
					pointColor : 'rgba(12, 80, 89, 1)',
					pointStrokeColor : '#fff',
					pointHighlightFill : '#fff',
					pointHighlightStroke : 'rgba(12, 80, 89, 1)',
					data : [randomScalingFactor(),randomScalingFactor(),randomScalingFactor(),randomScalingFactor(),randomScalingFactor(),randomScalingFactor(),randomScalingFactor()]
				}
			]
		}
		window.onload = function(){
			var ctx = document.getElementById('sales').getContext('2d');
			window.myLine = new Chart(ctx).Line(lineChartData, {
				responsive: true,
				bezierCurve: false,
				scaleLabel: '$<%=value%>',
				ooltipTemplate: '<%if (label){%><%=label%>: <%}%>$<%=value%>',
				multiTooltipTemplate: '$<%=value%>',
			});
			var ctx2 = document.getElementById('traffic').getContext('2d');
			window.myLine = new Chart(ctx2).Line(lineChartData2, {
				responsive: true,
				bezierCurve: false
			});
		}
		</script>";

		if( $this->input->post('update_listing') ) {

			$post = $this->input->post();
			unset( $post['update_listing'] );
			$this->listing_model->update_listing( $post, $id );

			$this->session->set_flashdata( 'listing', 'Your listing details have been updated' );

			redirect( 'user/listing/' . $id );
		}

		$data['id'] = $id;
		$data['gallery'] = json_decode( $this->listing_model->get_gallery( $id ) );
		$data['listing'] = $check;

		$this->load->view( 'users/listing', $data );
	}

	function ad( $id ) {

		$data['ads'] = $this->ads_model->get_ad( $id );
        $data['listings'] = $this->listing_model->get_by_user( $data['ads']->user_id );
        $data['gallery'] = $this->user_model->get_gallery( $data['ads']->user_id, false );

		if ( $this->user->ID != $data['ads']->user_id )
			die('you do not have privileges to access this page');

		if($this->input->post('update_ads'))
        {
        	unset($_POST['file']);
            $this->ads_model->update_ad($this->input->post(), $id);
            $this->session->set_flashdata('ads', 'AD details have been updated.');
            redirect('user/ad/'.$id);
        }

		$this->load->view( 'users/ad_details', $data );
	}

	public function footer_ad($postal_code, $range){

		return $this->listing_model->get_footer_ad( $postal_code, $range, 5, 0 );
		
	
	}

	
}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */