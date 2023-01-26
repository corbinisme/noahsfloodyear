<?php

namespace Drupal\video_greeting\Plugin\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Component\Render\FormattableMarkup;

/**
 * Provides a Video Greeting' Block.
 *
 * @Block(
 *   id = "video_greeting",
 *   admin_label = @Translation("Video Greeting"),
 *   category = @Translation("Welcome"),
 * )
 */


 class VideoGreetingBlock extends BlockBase {

    /**
     * {@inheritdoc}
     */


    public function build() {
  
    
      $markup = 
      '<div class="video-section hidden">
        <a href="javascript:void(0)" style="z-index:99999999" id="placeholderImg" onclick="playVid()">
      <img alt="" src="/Content/files/videoStandby.png">
      </a>
     <div class="gsVideo gsHome" id="gsVideo">
        <video id="movingAlphaDemo" style="display: none;">
          <source src="/Content/video/bcpNew.webm" type="video/webm">
          <source src="/Content/video/test.mp4" type="video/mp4">
          <source src="/Content/video/bcpNew.ogv" type="video/ogv">
        </video><canvas width="448" height="649" class="seeThru-display"></canvas><canvas width="448" height="1298" class="seeThru-buffer" style="display: none;"></canvas>
        <canvas width="448" height="649" class="seeThru-display"></canvas>
        <canvas width="448" height="1298" class="seeThru-buffer" style="display: none;"></canvas>
        <div class="CloseVideoButton" onclick="removeVideo()">Click to Close</div>
      </div></div>
        ';
      
      return [
          '#markup' => $this->t($markup),
          '#attached' => [
              'library' => [
                'video_greeting/welcome',
              ]
          ],
      
        
  
      ];
    }
  
  }