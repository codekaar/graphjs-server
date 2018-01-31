<?php

/*
 * This file is part of the Pho package.
 *
 * (c) Emre Sokullu <emre@phonetworks.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

 namespace GraphPress\Controllers;

use CapMousse\ReactRestify\Http\Request;
use CapMousse\ReactRestify\Http\Response;
use CapMousse\ReactRestify\Http\Session;
use Pho\Kernel\Kernel;
use Valitron\Validator;
use PhoNetworksAutogenerated\User;
use Pho\Lib\Graph\ID;


class ProfileController extends \Pho\Server\Rest\Controllers\AbstractController 
{
    /**
     * Get Profile
     * 
     * @score 10/10
     *
     * @param Request $request
     * @param Response $response
     * @param Kernel $kernel
     * @return void
     */
    public function getProfile(Request $request, Response $response, Kernel $kernel)
    {
        $data = $request->getQueryParams();
        $v = new Validator($data);
        $v->rule('required', ['id']);
        if(!$v->validate()) {
            $this->fail($response, "Valid user ID required.");
            return;
        }
        if(!preg_match("/^[0-9a-fA-F][0-9a-fA-F]{30}[0-9a-fA-F]$/", $data["id"])) {
            $this->fail($response, "Invalid user ID");
            return;
        }
        $user = $kernel->gs()->node($data["id"]);
        if(!$user instanceof User) {
            $this->fail($response, "Invalid user ID");
            return;
        }
        $response->writeJson([
            "status"=>"success", 
            "profile" => array_filter(
                $user->attributes()->toArray(), 
                function(string $key): bool {
                    return strtolower($key) != "password";
                },
                ARRAY_FILTER_USE_KEY
            )
        ])->end();
    }

    /**
     * Set Profile
     * 
     * @score 5/10 (username not unique!, birthday check, setAvatar does not seem to be working)
     *
     * @param Request $request
     * @param Response $response
     * @param Session $session
     * @param Kernel $kernel
     * @param string $id
     * @return void
     */
    public function setProfile(Request $request, Response $response, Session $session, Kernel $kernel, string $id)
    {
        // Avatar, Birthday, About, Username
        $data = $request->getQueryParams();
        $v = new Validator($data);
        $v->rule('url', ['avatar']);
        if(!$v->validate()) {
            $this->fail($response, "Avatar URL invalid.");
            return;
        }
        $v->rule('dateBefore', ['birthday'], "13 years ago");
        if(!$v->validate()) {
            $this->fail($response, "Birthday invalid.");
            return;
        }
        if(isset($data["username"]) && !preg_match("/^[a-zA-Z0-9_]{1,12}$/", $data["username"])) {
            $this->fail($response, "Invalid username");
            return;
        }

        $i = $kernel->gs()->node($id);
        $sets = [];

        if(isset($data["username"])) {
            $sets[] = "username";
            $i->setUsername($data["username"]);
        }

        if(isset($data["birthday"])) {
            $sets[] = "birthday";
            $i->setBirthday($data["birthday"]);
        }

        if(isset($data["avatar"])) {
            $sets[] = "avatar";
            $i->setAvatar($data["avatar"]);
        }

        if(isset($data["about"])) {
            $sets[] = "about";
            $i->setAbout($data["about"]);
        }

        if(count($sets)==0) {
            $this->fail($response, "No field to set");
            return;
        }
        
        $response->writeJson([
            "status"=>"success", 
            "message" => sprintf("Following fields set successfully: %s", implode(", ", $sets))
        ])->end();

    }
}