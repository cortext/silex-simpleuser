<?php

namespace SimpleUser;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

use InvalidArgumentException;

/**
 * Controller with actions for handling form-based authentication and user management.
 *
 * @package SimpleUser
 */
class UserController
{
    /** @var UserManager */
    protected $userManager;

    protected $layoutTemplate = '@user/layout.twig';

    /**
     * Constructor.
     *
     * @param UserManager $userManager
     * @param array $options
     */
    public function __construct(UserManager $userManager, $options = array())
    {
        $this->userManager = $userManager;

        if (!empty($options)) {
            $this->setOptions($options);
        }
    }

    /**
     * @param array $options
     */
    public function setOptions(array $options)
    {
        if (array_key_exists('layout_template', $options)) {
            $this->layoutTemplate = $options['layout_template'];
        }
    }

    /**
     * @param string $layoutTemplate
     */
    public function setLayoutTemplate($layoutTemplate)
    {
        $this->layoutTemplate = $layoutTemplate;
    }

    /**
     * Login action.
     *
     * @param Application $app
     * @param Request $request
     * @return Response
     */
    public function loginAction(Application $app, Request $request)
    {
        return $app['twig']->render('@user/login.twig', array(
            'layout_template' => $this->layoutTemplate,
            'error' => $app['security.last_error']($request),
            'last_username' => $app['session']->get('_security.last_username'),
            'platform_name' => $app['parameters']['platform_name'],
            'allowRememberMe' => isset($app['security.remember_me.response_listener']),
        ));
    }

    /**
     * Register action.
     *
     * @param Application $app
     * @param Request $request
     * @return Response
     */
    public function registerAction(Application $app, Request $request)
    {
        if ($request->isMethod('POST')) {
            try {
                $user = $this->createUserFromRequest($request);
                $this->userManager->insert($user);
                $callback_url = $request->request->get('callback');
                $app['session']->getFlashBag()->set('alert', 'account created');

                // Log the user in to the new account.
                if (null !== ($current_token = $app['security']->getToken())) {
                    $providerKey = method_exists($current_token, 'getProviderKey') ? $current_token->getProviderKey() : $current_token->getKey();
                    $token = new UsernamePasswordToken($user, null, $providerKey);
                    $app['security']->setToken($token);
                }


                //mailing to user and admins when registered
                $mailAdminFrom = $app['parameters']['admin'] or "webmaster@cortext.fr";
                $listAdminCortext = $app['parameters']['adminList'] or "coeur@cortext.fr";

                //user mail
                $messageUser = \Swift_Message::newInstance()
                ->setSubject('[Cortext] Welcome')
                ->setFrom(array($mailAdminFrom))
                ->setTo(array($user->getEmail()))
                ->setBody($app['twig']->render('@user/emailRegister.twig', array('user'=>$user, 'callback_url'=>$callback_url)));

                $messageAdmin = \Swift_Message::newInstance()
                ->setSubject('[Cortext] New user registered on '.date("Y-m-d H:i:s"))
                ->setFrom(array($mailAdminFrom))
                ->setTo(array($listAdminCortext))
                ->setBody($app['twig']->render('@user/emailRegisterAdmin.twig', array('user'=>$user, 'callback_url'=>$callback_url)));
                
                //sending mail to admin and deal with errors               
                if( $app['mailer']->send($messageAdmin))//send email  
                {
                    $app['monolog']->info("Sended mail : ".$messageAdmin);
                }         
                else
                {
                    $app['monolog']->error("ERROR while send admin mail : ".$messageAdmin);
                }
                

                //sending mail to user and deal with errors               
                if( $app['mailer']->send($messageUser))//send email  
                {
                    $app['monolog']->info("Sended mail : ".$messageUser);
                }         
                else
                {
                    $app['monolog']->error("ERROR while send registration mail : ".$messageUser);
                }
                
                //redirect to callback if present
                if($callback_url)
                  return $app->redirect($callback_url);
                else
                  return $app->redirect($app['url_generator']->generate('user.view', array('id' => $user->getId())));

            } catch (InvalidArgumentException $e) {
                $error = $e->getMessage();
            }
        }  
        //die(print_r($request->query, true));
        return $app['twig']->render('@user/register.twig', array(
            'layout_template' => $this->layoutTemplate,
            'error' => isset($error) ? $error : null,
            'name' => $request->request->get('name'),
            'email' => $request->request->get('email'),
            'callback' => $request->query->get('callback'),
            'platform_name' => $app['parameters']['platform_name'],
            'imageUrl' => null
        ));
    }
    
    /**
     * sends a new password by email
     * @param \Silex\Application $app
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function forgotPasswordAction(Application $app, Request $request)
    {
        
        if($request->isMethod('POST'))
        {
            $email = $request->get('email');
            //generate the new password$request->get('email')
           $user =  $this->userManager->loadUserByUsername($email);
           $newPass = $this->userManager->resetUserPassword($user);
           
            //create user email
            $messageContent = "Hi,
this is an automated message from Cortext Authentification : you requested a password change. 
Please find below your new password :\n\n________________\n\n".$newPass."\n________________\n\n
Make sure you change it the next time you log into Cortext !\n\n
\n\n Best regards,
\n the Cortext Administration Team";

            $message = \Swift_Message::newInstance()
            ->setSubject('[Cortext] New Password')
            ->setFrom(array('webmaster@cortext.fr'))
            ->setTo(array($email))
            ->setBody($messageContent);

            //send email
           
            $app['monolog']->info("Sending mail : ".$messageContent);
            
            if( $app['mailer']->send($message))             
            {
                //display confirmation
                $app['monolog']->info("Sended forgot passwd mail to ".$email);
                return $app['twig']->render('@user/forgotPassword.twig', array('requestSent'=>true,'email'=>$email,  'layout_template' => $this->layoutTemplate ));
            }
            else 
            {
                $app['monolog']->error("ERROR while send forgot passwd mail to : ".$email);
                throw new ErrorException('Mail has encountered an error while sending the password, please contact admin.');
                return 1;
            }

        }
        else
        {
              //display form
            return $app['twig']->render('@user/forgotPassword.twig', array('requestSent'=>false,  'layout_template' => $this->layoutTemplate));
        }
         
    }

    /**
     * @param Request $request
     * @return User
     * @throws InvalidArgumentException
     */
    protected function createUserFromRequest(Request $request)
    {
        if ($request->request->get('password') != $request->request->get('confirm_password')) {
            throw new InvalidArgumentException('Passwords don\'t match.');
        }

        $user = $this->userManager->createUser(
            $request->request->get('email'),
            $request->request->get('password'),
            $request->request->get('name') ?: null,
            array(),
            $request->request->get('city'),
            $request->request->get('country'),
            $request->request->get('institution') );

        $errors = $this->userManager->validate($user);
        if (!empty($errors)) {
            throw new InvalidArgumentException(implode("\n", $errors));
        }

        return $user;
    }

    /**
     * View user action.
     *
     * @param Application $app
     * @param Request $request
     * @param int $id
     * @return Response
     * @throws NotFoundHttpException if no user is found with that ID.
     */
    public function viewAction(Application $app, Request $request, $id)
    {
        $user = $this->userManager->getUser($id);
        $userProfile = $this->userManager->getUserProfile($id);
        $userProfile['imageUrl'] = $this->getGravatarUrl($user->getEmail());
        if (!$user) {
            throw new NotFoundHttpException('No user was found with that ID.');
        }

        $context = array(
            'layout_template' => $this->layoutTemplate,
            'user' => $user,
            'profile' => $userProfile,
            'imageUrl' => $this->getGravatarUrl($user->getEmail()),
        );

        $guest = $this->userManager->getCurrentUser();
        if ($guest) {
            $context['guestImageUrl'] = $this->getGravatarUrl($guest->getEmail());
        }

        return $app['twig']->render('@user/view.twig', $context);
    }

    public function viewSelfAction(Application $app, Request $request) {
        if (!$app['user']) {
            return $app->redirect($app['url_generator']->generate('user.login'));
        }
        $callback_url = $request->get('callback_url');

        return $app->redirect($app['url_generator']->generate('user.view', array('id' => $app['user']->getId(), 'callback_url' => $callback_url )));
    }

    /**
     * @param string $email
     * @param int $size
     * @return string
     */
    public function getGravatarUrl($email, $size = 80)
    {
        // See https://en.gravatar.com/site/implement/images/ for available options.
        return '//www.gravatar.com/avatar/' . md5(strtolower(trim($email))) . '?s=' . $size . '&d=identicon';
    }

    /**
     * Edit user action. This function checks if request is valid, if new fields are valid, and then update
     * user in DB, before outputting a result page depending of the result of the action.
     *
     * @param Application $app
     * @param Request $request
     * @param int $id
     * @return Response
     * @throws NotFoundHttpException if no user is found with that ID.
     */
    public function editAction(Application $app, Request $request, $id)
    {
        /* Trace edition */
        $app['monolog']->debug("TRACE:editAction:edit:".$id);
        $app['monolog']->debug("TRACE:editAction:edit:".$id);

        /* Initialize errors list */
        $errors = array();

        /* Get the user from the DB, and put it in an object */
        $user = $this->userManager->getUser($id);

        /* If user Id is invalid and user doesn't exists, throw exception */
        if (!$user) {
            $app['monolog']->debug("TRACE:editAction:userNotFound:".$id);
            throw new NotFoundHttpException('No user was found with that ID.');
        }

        /* Check if request method is POST, so from a form */
        if ($request->isMethod('POST')) {

            /* Update fields of the objects via his setters, with data from the post request */

            /* User Name */
            if ( $user->getName() != $request->request->get('name') ) $app['monolog']->debug("TRACE:editAction:modification:name:".$user->getName()."=>".$request->request->get('name').":".$id);
            $user->setName($request->request->get('name'));

            /* User Email */
            if ( $user->getEmail() != $request->request->get('email') ) $app['monolog']->debug("TRACE:editAction:modification:email:".$user->getEmail()."=>".$request->request->get('email').":".$id);
            $user->setEmail($request->request->get('email'));

            /* User Password */
            if ($request->request->get('password')) {
                if ($request->request->get('password') != $request->request->get('confirm_password')) {
                    $errors['password'] = 'Passwords don\'t match.';
                } else {
                    $app['monolog']->debug("TRACE:editAction:modification:password:".$id);
                    $this->userManager->setUserPassword($user, $request->request->get('password'));
                }
            }

            /* User Roles */
            if ($app['security']->isGranted('ROLE_ADMIN') && $request->request->has('roles')) {
                $app['monolog']->debug("TRACE:editAction:modification:roles:".$id);
                $user->setRoles($request->request->get('roles'));
            }

            /* User Description */
            if ( $user->getDescription() != $request->request->get('description') ) $app['monolog']->debug("TRACE:editAction:modification:description:".$id);
            $user->setDescription($request->request->get('description'));

            /* User Website */
            if ( $user->getWebsite() != $request->request->get('website') ) $app['monolog']->debug("TRACE:editAction:modification:website:".$id);
            $user->setWebsite($request->request->get('website'));

            /* User Birthdate */
            if ( $user->getBirthdate() != $request->request->get('birthdate') ) $app['monolog']->debug("TRACE:editAction:modification:birthdate:".$user->getBirthdate()."=>".$request->request->get('birthdate').":".$id);
            $user->setBirthdate($request->request->get('birthdate'));

            /* User City */
            if ( $user->getCity() != $request->request->get('city') ) $app['monolog']->debug("TRACE:editAction:modification:city:".$user->getCity()."=>".$request->request->get('city').":".$id);
            $user->setCity($request->request->get('city'));

            /* User Country */
            if ( $user->getCountry() != $request->request->get('country') ) $app['monolog']->debug("TRACE:editAction:modification:country:".$user->getCountry()."=>".$request->request->get('country').":".$id);
            $user->setCountry($request->request->get('country'));

            /* User Institution */
            if ( $user->getInstitution() != $request->request->get('institution') ) $app['monolog']->debug("TRACE:editAction:modification:institution:".$user->getInstitution()."=>".$request->request->get('institution').":".$id);
            $user->setInstitution($request->request->get('institution'));

            /* User Activity Domain */
            if ( $user->getActivitydomain() != $request->request->get('activitydomain') ) $app['monolog']->debug("TRACE:editAction:modification:activitydomain:".$user->getActivitydomain()."=>".$request->request->get('activitydomain').":".$id);
            $user->setActivitydomain($request->request->get('activitydomain'));

            /* User Research Domain */
            if ( $user->getResearchdomain() != $request->request->get('researchdomain') ) $app['monolog']->debug("TRACE:editAction:modification:researchdomain:".$user->getResearchdomain()."=>".$request->request->get('researchdomain').":".$id);
            $user->setResearchdomain($request->request->get('researchdomain'));

             /* User Authorizations */
            if ( $user->getAuthorizations() != $request->request->get('authorizations') ) $app['monolog']->debug("TRACE:editAction:modification:researchdomain:".$user->getResearchdomain()."=>".$request->request->get('researchdomain').":".$id);
            $user->setAuthorizations($request->request->get('authorizations'));


            /* Call to the validate function of the user, to check if the user is correct */
            $errors += $this->userManager->validate($user);

            /* Check if any error has been found in the new fields */
            if (empty($errors)) {

                /* Trace user update */
                $app['monolog']->debug("TRACE:editAction:updateDB:".$id);

                /* Update the user in DB only if no error */
                $this->userManager->update($user);

                /* Prepare result message */
                $msg = 'Saved account information.' . ($request->request->get('password') ? ' Changed password.' : '');

                /* Output result message */
                $app['session']->getFlashBag()->set('alert', $msg);
            } else {
                /* There is errors in new fields, log messages  */
                ob_start(); var_dump($errors);
                $app['monolog']->debug("TRACE:editAction:errors:".ob_get_clean().":".$id);

                /* If errors tell that nothing is saved */
                $errors['hint'] = "Account information was not saved.";
            }
        }

        /* Render the result page with twig and return the result web page */
        return $app['twig']->render('@user/edit.twig', array(
            'layout_template' => $this->layoutTemplate,
            'error' => implode("\n", $errors),
            'user' => $user,
            'available_roles' => array('ROLE_USER', 'ROLE_ADMIN'),
            'imageUrl' => $this->getGravatarUrl($user->getEmail()),
            'prcComplete' => (int)($user->getPrcComplete()*100),
        ));
    }

    /**
     * Que fait cette fonction ?
     */
    public function listAction(Application $app, Request $request)
    {
        $limit = $request->get('limit') ?: 50;
        $offset = $request->get('offset') ?: 0;
        $order_by = $request->get('order_by') ?: 'id';
        $order_dir = $request->get('order_dir') == 'DESC' ? 'DESC' : 'ASC';
        $callback_url = $request->get('callback_url');

        $numResults = $this->userManager->findCount();

        $users = $this->userManager->findBy(array(), array(
            'limit' => array($offset, $limit),
            'order_by' => array($order_by, $order_dir),
        ));

        foreach ($users as $user) {
            $user->imageUrl = $this->getGravatarUrl($user->getEmail(), 40);
        }

        $nextUrl = $prevUrl = null;
        if ($numResults > $limit) {
            $nextOffset = ($offset + $limit) < $numResults  ? $offset + $limit : null;
            $prevOffset = $offset > 0 ? (($offset - $limit) > 0 ? $offset - $limit : 0) : null;

            $baseUrl = $app['url_generator']->generate('user.list') . '?limit=' . $limit . '&order_by=' . $order_by . '&order_dir=' . $order_dir;
            if ($nextOffset !== null) {
                $nextUrl = $baseUrl . '&offset=' . $nextOffset;
            }
            if ($prevOffset !== null) {
                $prevUrl = $baseUrl . '&offset=' . $prevOffset;
            }
        }
        $firstResult = $offset + 1;
        $lastResult = ($offset + $limit) > $numResults ? $numResults : $offset + $limit;

        return $app['twig']->render('@user/list.twig', array(
            'layout_template' => '@user/layoutList.twig',
            'users' => $users,
            'callbackUrl' => $callback_url,
            'numResults' => $numResults,
            'nextUrl' => $nextUrl,
            'prevUrl' => $prevUrl,
            'firstResult' => $firstResult,
            'lastResult' => $lastResult,
        ));

    }
}
