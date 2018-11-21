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
 use PhoNetworksAutogenerated\User;
 use PhoNetworksAutogenerated\Page;
use PhoNetworksAutogenerated\Blog;
use PhoNetworksAutogenerated\UserOut\Comment;
use Pho\Lib\Graph\ID;
 

  /**
 * Takes care of Blog functionality
 * 
 * @author Emre Sokullu <emre@phonetworks.org>
 */
class BlogController extends AbstractController
{
    // postBlog
    // > $user->postBlog("title", "content");
    // editBlog
    // dleeteBlog

    public function fetchAll(Request $request, Response $response, Session $session, Kernel $kernel)
    {
        
        $blogs = [];
        $everything = $kernel->graph()->members();
        $is_moderated = $kernel->graph()->getCommentsModerated();
        foreach($everything as $thing) {

            if($thing instanceof Blog) {
                error_log("blog id: ".$thing->id()->toString());
                $publish_time =  intval($thing->getPublishTime());
                $comments = $is_moderated ?
                    array_filter($thing->getComments(), function(Comment $comm) {
                        return $comm->getPending() !== true;
                    })
                    : $thing->getComments();
                $comment_count = (string) count($comments);
                //eval(\Psy\sh());
                $blogs[] = [
                    "id" => (string) $thing->id(),
                    "title" => $thing->getTitle(),
                    "summary" => $thing->getContent(),
                    "author" => [
                        "id" => (string) $thing->getAuthor()->id(),
                       "username" => (string) $thing->getAuthor()->getUsername()
                    ],
                    "start_time" => (string) $thing->getCreateTime(),
                    "is_draft" => ($publish_time == 0),
                    "last_edit" => (string) $thing->getLastEditTime(),
                    "publish_time" => (string) $publish_time,
                    "comment_count" => $comment_count
                ];
            }
        }
        $this->succeed(
            $response, [
                "blogs" => $blogs
            ]
        );
    }

    public function fetch(Request $request, Response $response, Session $session, Kernel $kernel)
    {
        $data = $request->getQueryParams();
        $validation = $this->validator->validate($data, [
            'id' => 'required',
        ]);
        if($validation->fails()) {
            return $this->fail($response, "Title (up to 255 chars) and Content are required.");
        }
        try {
            $blog = $kernel->gs()->node($data["id"]);
        }
        catch(\Exception $e) {
            return $this->fail($response, "No such Blog Post");
        }
        if(!$blog instanceof Blog) {
            return $this->fail($response, "Given id is not a blog post");
        }
        $publish_time =  intval($blog->getPublishTime());
        $this->succeed(
            $response, [
                "blog" => [
                    "id" => (string) $blog->id(),
                    "title" => $blog->getTitle(),
                    "summary" => $blog->getContent(),
                    "author" => [
                        "id" => (string) $blog->getAuthor()->id(),
                       "username" => (string) $blog->getAuthor()->getUsername()
                    ],
                    "start_time" => (string) $blog->getCreateTime(),
                    "is_draft" => ($publish_time == 0),
                    "last_edit" => (string) $blog->getLastEditTime(),
                    "publish_time" => (string) $publish_time
                ]
            ]
        );
    }



    public function post(Request $request, Response $response, Session $session, Kernel $kernel)
    {
        if(is_null($id = $this->dependOnSession(...\func_get_args()))) {
            return;
        }
        $data = $request->getQueryParams();
        $validation = $this->validator->validate($data, [
            'title' => 'required|max:255',
            'content' => 'required',
        ]);
        if($validation->fails()) {
            $this->fail($response, "Title (up to 255 chars) and Content are required.");
            return;
        }
        $i = $kernel->gs()->node($id);
        $can_edit = $this->canEdit($i);
        if(!$can_edit) {
            return $this->fail($response, "No privileges for blog posts");
        }
        $blog = $i->postBlog($data["title"], $data["content"]);
        $this->succeed(
            $response, [
                "id" => (string) $blog->id()
            ]
        );
    }


    public function edit(Request $request, Response $response, Session $session, Kernel $kernel) 
    {
     if(is_null($id = $this->dependOnSession(...\func_get_args()))) {
            return;
        }
     $data = $request->getQueryParams();
        $validation = $this->validator->validate($data, [
            'id' => 'required',
            'title'=>'required',
            'content' => 'required',
        ]);
        if($validation->fails()) {
            $this->fail($response, "ID, Title and Content are required.");
            return;
        }
        $i = $kernel->gs()->node($id);
        $can_edit = $this->canEdit($i);
        if(!$can_edit) {
            return $this->fail($response, "No privileges for blog posts");
        }
        try {
            $entity = $kernel->gs()->entity($data["id"]);
        }
        catch(\Exception $e) 
        {
            return $this->fail($response, "Invalid ID");
        }
        if(!$entity instanceof Blog) {
            $this->fail($response, "Given ID is not a Blog.");
            return;
        }
        try {
        $i->edit($entity)->setTitle($data["title"]);
        $i->edit($entity)->setContent($data["content"]);
        $i->edit($entity)->setLastEditTime(time());
        }
     catch(\Exception $e) {
        $this->fail($response, $e->getMessage());
            return;
     }
     $this->succeed($response);
    }


    public function delete(Request $request, Response $response, Session $session, Kernel $kernel)
    {
        if(is_null($id = $this->dependOnSession(...\func_get_args()))) {
            return;
        }
        $data = $request->getQueryParams();
        $validation = $this->validator->validate($data, [
            'id' => 'required',
        ]);
        if($validation->fails()) {
            $this->fail($response, "ID is required.");
            return;
        }
        try {
            $i = $kernel->gs()->node($id);
        }
        catch (\Exception $e) {
            return $this->fail($response, "Invalid ID");
        }
            try {
            $blog = $kernel->gs()->node($data["id"]);
            }
            catch(\Exception $e) {
                return $this->fail($response, "Invalid ID");
            }
            if(!$blog instanceof Blog) {
                return $this->fail($response, "Invalid ID");
            }
            // check author
            if(
                !$i->id()->equals($kernel->founder()->id()) 
                &&
                !$blog->getAuthor()->id()->equals($i->id())
            ) {
                return $this->fail($response, "No privileges to delete this content");
            }
            try {
              $blog->destroy(); 
            }
            catch(\Pho\Framework\Exceptions\InvalidParticleMethodException $e) {
                error_log($e->getMessage());
                return $this->fail($response, "Problem destroying the node");
            }
            return $this->succeed($response);
        
    }


    protected function canEdit($actor)
    {
        return isset($actor->attributes()->is_editor) && (bool) $actor->attributes()->is_editor;
    }


    public function publish(Request $request, Response $response, Session $session, Kernel $kernel)
    {

     if(is_null($id = $this->dependOnSession(...\func_get_args()))) {
        return;
    }
 $data = $request->getQueryParams();
    $validation = $this->validator->validate($data, [
        'id' => 'required',
    ]);
    if($validation->fails()) {
        $this->fail($response, "ID is required.");
        return;
    }
    $i = $kernel->gs()->node($id);
    try {
    $entity = $kernel->gs()->entity($data["id"]);
    }
    catch(\Exception $e) 
    {
        return $this->fail($response, "Invalid ID");
    }
    if(!$entity instanceof Blog) {
        $this->fail($response, "Given ID is not a Blog.");
        return;
    }
    $can_edit = $this->canEdit($i);
        if(!$can_edit) {
            return $this->fail($response, "No privileges for blog posts");
        }
    try {
    $i->edit($entity)->setPublishTime(time());
    }
 catch(\Exception $e) {
    $this->fail($response, $e->getMessage());
        return;
 }
 $this->succeed($response);
    }

    public function unpublish(Request $request, Response $response, Session $session, Kernel $kernel)
    {

     if(is_null($id = $this->dependOnSession(...\func_get_args()))) {
        return;
    }
 $data = $request->getQueryParams();
    $validation = $this->validator->validate($data, [
        'id' => 'required'
    ]);
    if($validation->fails()) {
        $this->fail($response, "ID is required.");
        return;
    }
    $i = $kernel->gs()->node($id);
    try {
    $entity = $kernel->gs()->entity($data["id"]);
    }
    catch(\Exception $e) 
    {
        return $this->fail($response, "Invalid ID");
    }
    $can_edit = $this->canEdit($i);
        if(!$can_edit) {
            return $this->fail($response, "No privileges for blog posts");
        }
    if(!$entity instanceof Blog) {
        $this->fail($response, "Given ID is not a Blog.");
        return;
    }
    try {
        $i->edit($entity)->setPublishTime(0);
    }
 catch(\Exception $e) {
    $this->fail($response, $e->getMessage());
        return;
 }
 $this->succeed($response);
    }

}