<?php

namespace SimpleUser;

use Symfony\Component\Security\Core\User\UserInterface;

/**
 * A simple User model.
 *
 * @package SimpleUser
 */
class User implements UserInterface, \Serializable
{
    protected $id;
    protected $email;
    protected $password;
    protected $salt;
    protected $roles = array();
    protected $name = '';
    protected $timeCreated;
    protected $description;
    protected $website;
    protected $birthdate;
    protected $last_connexion; 
    protected $city;
    protected $country;
    protected $institution;
    protected $activity_domain;
    protected $research_domain;
    protected $authorizations;

    /**
     * Constructor.
     *
     * @param string $email
     */
    public function __construct($email)
    {
        $this->email = $email;
        $this->timeCreated = time();
        $this->salt = base_convert(sha1(uniqid(mt_rand(), true)), 16, 36);
        //$this->last_connexion = time();
    }

    /**
     * Returns the roles granted to the user. Note that all users have the ROLE_USER role.
     *
     * @return array A list of the user's roles.
     */
    public function getRoles()
    {
        $roles = $this->roles;

        // Every user must have at least one role, per Silex security docs.
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * Set the user's roles to the given list.
     *
     * @param array $roles
     */
    public function setRoles(array $roles)
    {
        $this->roles = array();

        foreach ($roles as $role) {
            $this->addRole($role);
        }
    }

    /**
     * Test whether the user has the given role.
     *
     * @param string $role
     * @return bool
     */
    public function hasRole($role)
    {
        return in_array(strtoupper($role), $this->getRoles(), true);
    }

    /**
     * Add the given role to the user.
     *
     * @param string $role
     */
    public function addRole($role)
    {
        $role = strtoupper($role);

        if ($role === 'ROLE_USER') {
            return;
        }

        if (!$this->hasRole($role)) {
            $this->roles[] = $role;
        }
    }

    /**
     * Remove the given role from the user.
     *
     * @param string $role
     */
    public function removeRole($role)
    {
        if (false !== $key = array_search(strtoupper($role), $this->roles, true)) {
            unset($this->roles[$key]);
            $this->roles = array_values($this->roles);
        }
    }

    /**
     * Set the user ID.
     *
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Get the user ID.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the encoded password used to authenticate the user.
     *
     * On authentication, a plain-text password will be salted,
     * encoded, and then compared to this value.
     *
     * @return string The encoded password.
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set the encoded password.
     *
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     * Set the salt that should be used to encode the password.
     *
     * @param string $salt
     */
    public function setSalt($salt)
    {
        $this->salt = $salt;
    }

    /**
     * Returns the salt that was originally used to encode the password.
     *
     * This can return null if the password was not encoded using a salt.
     *
     * @return string The salt
     */
    public function getSalt()
    {
        return $this->salt;
    }

    /**
     * Returns the email address, which serves as the username used to authenticate the user.
     *
     * This method is required by the UserInterface.
     *
     * @return string The username
     */
    public function getUsername()
    {
        return $this->email;
    }

    /**
     * @return string The user's email address.
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns the name, if set, or else "Anonymous {id}".
     *
     * @return string
     */
    public function getDisplayName()
    {
        return $this->name ?: 'Anonymous ' . $this->id;
    }

    /**
     * Set the time the user was originally created.
     *
     * @param int $timeCreated A timestamp value.
     */
    public function setTimeCreated($timeCreated)
    {
        $this->timeCreated = $timeCreated;
    }

    /**
     * Set the time the user was originally created.
     *
     * @return int
     */
    public function getTimeCreated()
    {
        return $this->timeCreated;
    }

    /**
     * Get the description of the user.
     *
     * @return string The description.
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set the description of the user.
     *
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * Get the website of the user.
     *
     * @return string The website.
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * Set the website of the user.
     *
     * @param string $website
     */
    public function setWebsite($website)
    {
        /* Reformatage de l'URL s'il manque le protocole */
        if ( substr( $website, 0, 7 ) != "http://" && 
             substr( $website, 0, 8 ) != "https://" && 
             substr( $website, 0, 2 ) != "//" && 
             $website != "" ) {
            $website = "http://" . $website;
        }
        $this->website = $website;
    }

    /**
     * Get the birthdate of the user.
     *
     * @return text The birthdate.
     */
    public function getBirthdate()
    {
        return $this->birthdate;
    }

    /**
     * Set the birthdate of the user.
     *
     * @param text $birthdate
     */
    public function setBirthdate($birthdate)
    {
        if ( $birthdate == "0000-00-00" ) {
            $birthdate = "";
        }
        $this->birthdate = $birthdate;
    }

    /**
     * Get the last connexion of the user.
     *
     * @return int The last connexion.
     */
    public function getLastConnexion()
    {
        return $this->last_connexion;
    }

    /**
     * Set the last connexion of the user.
     *
     * @param int $last_connexion
     */
    public function setLastConnexion($last_connexion)
    {
        $this->last_connexion = $last_connexion;
    }

    /**
     * Get the instituion of the user.
     *
     * @return string The institution.
     */
    public function getInstitution()
    {
        return $this->institution;
    }

    /**
     * Set the institution of the user.
     *
     * @param string $institution
     */
    public function setInstitution($institution)
    {
        $this->institution = $institution;
    }

    /**
     * Get the city of the user.
     *
     * @return string The city.
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Set the city of the user.
     *
     * @param string $city
     */
    public function setCity($city)
    {
        $this->city = $city;
    }

    /**
     * Get the country of the user.
     *
     * @return string The country.
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * Set the country of the user.
     *
     * @param string $country
     */
    public function setCountry($country)
    {
        $this->country = $country;
    }

    /**
     * Get the activity domain of the user.
     *
     * @return string The activity domain.
     */
    public function getActivitydomain()
    {
        return $this->activity_domain;
    }

    /**
     * Set the activity domain of the user.
     *
     * @param string $activity_domain
     */
    public function setActivitydomain($activity_domain)
    {
        $this->activity_domain = $activity_domain;
    }

    /**
     * Get the research domain of the user.
     *
     * @return string The research domain.
     */
    public function getResearchdomain()
    {
        return $this->research_domain;
    }

    /**
     * Set the research domain of the user.
     *
     * @param string $research_domain
     */
    public function setResearchdomain($research_domain)
    {
        $this->research_domain = $research_domain;
    }

    /**
     * Get the authorizations of the user.
     *
     * @return string the authorizations.
     */
    public function getAuthorizations()
    {
        return $this->authorizations;
    }

    /**
     * Get the one particular authorisation of the user.
     * @param  [type] $key [description]
     * @return [string]  the value of the authorization
     */
    public function getAuthorization($key)
    {
        $authArray = json_decode($this->authorizations, true);
        if(isset($authArray[$key]))
            return $authArray[$key];
        else
            return false;
    }

    /**
     * Set the authorizations of the user.
     *
     * @param string $authorizations
     */
    public function setAuthorizations($authorizations)
    {
        $this->authorizations = $authorizations;
    }

    /**
     * add or updates a new authorization  value (is a json object) based on a particular key
     * @param [string] $key
     * @param [json] $value
     */
    public function setAuthorization($key, $value)
    {
        $authArray = json_decode($this->getAuthorizations(), true);
        $authArray[$key] = $value;
        $this->setAuthorizations(json_encode($authArray));
    }

    /**
     * Get the pourcentage of how many this user information are complete
     * Function return a value between 0 and 1
     */
    public function getPrcComplete()
    {
        $cpt = 0; $tot = 0;
        $cpt += ( $this->getName() == "" ? 1 : 0 ); $tot++;
        $cpt += ( $this->getEmail() == "" ? 1 : 0 ); $tot++;
        $cpt += ( $this->getDescription() == "" ? 1 : 0 ); $tot++;
        $cpt += ( $this->getCity() == "" ? 1 : 0 ); $tot++;
        $cpt += ( $this->getCountry() == "" ? 1 : 0 ); $tot++;
        $cpt += ( $this->getInstitution() == "" ? 1 : 0 ); $tot++;
        $cpt += ( $this->getActivitydomain() == "" ? 1 : 0 ); $tot++;
        $cpt += ( $this->getResearchdomain() == "" ? 1 : 0 ); $tot++;
        $cpt += ( $this->getWebsite() == "" ? 1 : 0 ); $tot++;
        $cpt += ( $this->getBirthdate() == "" ? 1 : 0 ); $tot++;
        return 1 - $cpt / $tot ;
    }

    /**
     * Removes sensitive data from the user.
     *
     * This is a no-op, since we never store the plain text credentials in this object.
     * It's required by UserInterface.
     *
     * @return void
     */
    public function eraseCredentials()
    {
    }

    /**
     * The Symfony Security component stores a serialized User object in the session.
     * We only need it to store the user ID, because the user provider's refreshUser() method is called on each request
     * and reloads the user by its ID.
     *
     * @see \Serializable::serialize()
     */
    public function serialize()
    {
        return serialize(array(
            $this->id,
        ));
    }

    /**
     * @see \Serializable::unserialize()
     */
    public function unserialize($serialized)
    {
        list (
            $this->id,
            ) = unserialize($serialized);
    }

    /**
     * Validate the user object.
     *
     * @return array An array of error messages, or an ampty array if there were no errors.
     */
    public function validate()
    {
        $errors = array();

        if (!$this->getEmail()) {
            $errors['email'] = 'Email address is required.';
        } else if (!strpos($this->getEmail(), '@')) {
            // Basic email format sanity check. Real validation comes from sending them an email with a link they have to click.
            $errors['email'] = 'Email address appears to be invalid.';
        } else if (strlen($this->getEmail()) > 100) {
            $errors['email'] = 'Email address can\'t be longer than 100 characters.';
        }

        if (!$this->getPassword()) {
            $errors['password'] = 'Password is required.';
        } else if (strlen($this->getPassword()) > 255) {
            $errors['password'] = 'Password can\'t be longer than 255 characters.';
        }

        if (strlen($this->getName()) > 100) {
            $errors['name'] = 'Name can\'t be longer than 100 characters.';
        }

        if ( !preg_match("/^(19|20)[0-9]{2}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/",$this->getBirthdate()) && $this->getBirthdate() != "" ) {
            $errors['birthdate'] = 'Birthdate is invalid.';
        }

        return $errors;
    }

}
