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

                $message = \Swift_Message::newInstance()
                ->setSubject('[Cortext] Welcome')
                ->setFrom(array('webmaster@cortext.fr'))
                ->setTo(array($user->getEmail()))
                ->setBcc(array('webmaster@cortext.fr'))
                ->setBody($app['twig']->render('@user/emailRegister.twig', array('user'=>$user, 'callback_url'=>$callback_url)));

                
                if( $app['mailer']->send($message))//send email  
                {
                    $app['monolog']->info("Sended mail : ".$message);
                }         
                else
                {
                    $app['monolog']->error("ERROR while send registration mail : ".$message);
                }
                    
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
        //die(print_r($userProfile));
        if (!$user) {
            throw new NotFoundHttpException('No user was found with that ID.');
        }

        return $app['twig']->render('@user/view.twig', array(
            'layout_template' => $this->layoutTemplate,
            'user' => $user,
            'profile' => $userProfile,
            'imageUrl' => $this->getGravatarUrl($user->getEmail()),
        ));

    }

    public function viewSelfAction(Application $app) {
        if (!$app['user']) {
            return $app->redirect($app['url_generator']->generate('user.login'));
        }

        return $app->redirect($app['url_generator']->generate('user.view', array('id' => $app['user']->getId())));
    }

    /**
     * @param string $email
     * @param int $size
     * @return string
     */
    protected function getGravatarUrl($email, $size = 80)
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
            'layout_template' => $this->layoutTemplate,
            'users' => $users,
            'numResults' => $numResults,
            'nextUrl' => $nextUrl,
            'prevUrl' => $prevUrl,
            'firstResult' => $firstResult,
            'lastResult' => $lastResult,
        ));

    }
}
