<?php

/*
 * This file is part of the Pho package.
 *
 * (c) Emre Sokullu <emre@phonetworks.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

 namespace GraphJS\Controllers;

use CapMousse\ReactRestify\Http\Request;
use CapMousse\ReactRestify\Http\Response;
use CapMousse\ReactRestify\Http\Session;
use Pho\Kernel\Kernel;
use Valitron\Validator;
use PhoNetworksAutogenerated\User;
use Pho\Lib\Graph\ID;


/**
 * Takes care of Members
 * 
 * @author Emre Sokullu <emre@phonetworks.org>
 */
class MembersController extends AbstractController 
{
    /**
     * Get Members
     *
     * @param Request $request
     * @param Response $response
     * @param Kernel $kernel
     * 
     * @return void
     */
    public function getMembers(Request $request, Response $response, Kernel $kernel)
    {
        $nodes = $kernel->graph()->members();
        $members = [];
        foreach($nodes as $node) {
            if($node instanceof User)
                $members[(string) $node->id()] = [
                    "username" => (string) $node->getUsername(),
                    "avatar" => (string) $node->getAvatar()
                ];
        }
        $this->succeed($response, ["members" => $members]);
    }
 
   /**
     * Follow someone
     *
     * @param Request $request
     * @param Response $response
     * @param Kernel $kernel
     * 
     * @return void
     */
    public function follow(Request $request, Response $response, Kernel $kernel, Session $session)
    {
     
    }

}
