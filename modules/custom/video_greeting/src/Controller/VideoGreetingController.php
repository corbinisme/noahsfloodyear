<?php
namespace Drupal\video_greeting\Controller;

class VideoGreetingController {
    public function index() {
        return array(
            '#title' => 'Hello World!',
            '#markup' => 'Content for Hello World.'
        );
    }
}