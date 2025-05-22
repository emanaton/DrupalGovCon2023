<?php

namespace Drupal\vizzini_tweaks\Plugin\views\display_extender;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\views\Plugin\views\display_extender\DisplayExtenderPluginBase;

/**
 * Vizzini display extender plugin.
 *
 * @ingroup views_display_extender_plugins
 *
 * @ViewsDisplayExtender(
 *   id = "vizzini_display_extender",
 *   title = @Translation("Vizzini Display Extender"),
 *   help = @Translation("Extra settings for this view."),
 *   no_ui = FALSE
 * )
 */

class VizziniDisplayExtender extends DisplayExtenderPluginBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['subtitle'] = [];
    $options['suppress_headers'] = [];
    $options['move_result'] = FALSE;

    return $options;
  }

  public function buildOptionsForm(&$form, FormStateInterface $form_state) {

    switch ($form_state->get('section')) {
      case 'subtitle':
        $form['#title'] .= $this->t('Subtitle');

        $form['description'] = [
          '#type' => 'markup',
          '#markup' =>
            '<div>'
            . $this->t(
              'Optionally enter a subtitle to display at the top of the views exposed form.'
            )
            . '</div>',
        ];

        $form['subtitle'] = [
          '#type' => 'textfield',
          '#title' => 'Exposed Form Title',
          '#description' => 'Title value to display at the top of the Views Exposed Fields form.',
          '#default_value' => $this->options['subtitle'],
        ];

        break;

      case 'move_result':
        $form['#title'] .= $this->t('Move Result');

        $form['move_result'] = [
          '#type' => 'checkbox',
          '#title' => 'Move Result Block',
          '#description' => 'Move the result block to between the exposed form and the view content.',
          '#default_value' => $this->options['move_result'],
        ];
        break;

      case 'suppress_headers':

        // Build out a list of headers that this view has implemented.
        $headers = [];
        foreach ($this->view->getHandlers('header') as $header) {
          $headers[$header['id']] = t('@id (@table => @field => @val)', [
            '@id' => $header['id'],
            '@table' => $header['table'],
            '@field' => $header['field'],
            // The different handler types provide different arrays of data.
            // Rather than pull identifying information out based on the
            // particular handler type, this line pulls the likely keys to
            // provide contextual information that the user can use to identify
            // the correct block(s) to suppress.
            '@val' => implode(' => ', array_intersect_key($header, array_flip(['target', 'view_to_insert', 'plugin_id']))),
          ]);
        }

        $form['suppress_headers'] = [
          '#title' => 'Suppress Headers',
          '#type' => 'checkboxes',
          '#options' => !!$headers ? $headers : ['__na__' => 'This view has no headers...'],
          '#description' => 'Suppress the selected headers when results are displayed in the view.',
          '#default_value' => !!$headers ? $this->options['suppress_headers'] : ['__na__'],
          '#disabled' => !$headers,
        ];

        break;
    }

  }

  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    $form_values = $form_state->cleanValues()->getValues();
    $section = $form_state->get('section');

    switch ($section) {
      case 'subtitle':
        $this->options['subtitle'] = $form_values['subtitle'];
        break;

      case 'move_result':
        $this->options['move_result'] = $form_values['move_result'];
        break;

      case 'suppress_headers':
        $this->options['suppress_headers'] = $form_values['suppress_headers'];
        break;
    }

  }

  /**
   * Make all the vizinni options available for use in places like the theme
   * layer and in other hooks.
   */
  public function preExecute() {
    $this->view->vizzini = $this->options;
  }


  /**
   * Provide the default summary for options in the views UI.
   *
   * This output is returned as an array.
   */
  public function optionsSummary(&$categories, &$options) {
    $categories['vizzini'] = [
      'title' => $this->t('Vizzini Settings'),
      'column' => 'second',
    ];


    $subtitle = empty($this->options['subtitle'])
      ? $this->t('None')
      : $this->options['subtitle'];

    if (str_word_count($subtitle, 0) > 4) {
      $words = str_word_count($subtitle, 2);
      $pos = array_keys($words);
      $subtitle = substr($subtitle, 0, $pos[4]) . '...';
    }

    $options['subtitle'] = [
      'category' => 'vizzini',
      'title' => $this->t('Subtitle'),
      'value' => $subtitle
    ];

    $options['move_result'] = [
      'category' => 'vizzini',
      'title' => $this->t('Move Result Block'),
      'value' => ($this->options['move_result'] ? 'True' : 'False')
    ];

    $options['suppress_headers'] = [
      'category' => 'vizzini',
      'title' => $this->t('Suppressed Headers'),
      'value' => (
        !!($headers = array_filter($this->options['suppress_headers']))
          ? count($headers) . ' Suppressed'
          : 'None'
      )
    ];

  }


}
