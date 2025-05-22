<?php

namespace Drupal\vizzini_tweaks\Plugin\views\display_extender;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\views\Plugin\views\display_extender\DisplayExtenderPluginBase;

/**
 * Vizzini display extender plugin.
 *
 * Provides additional configuration options for Views including:
 * - Adding a subtitle to the exposed filters form
 * - Moving the result summary block between filters and content
 * - Suppressing specific header blocks when results are displayed
 *
 * This code is primarily for demonstration purposes, showcasing how to extend
 * Drupal Views with custom settings using DisplayExtenderPluginBase. It serves
 * as a learning tool and reference implementation for developers looking to
 * create their own view extensions.
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
   *
   * Defines the default options for the Vizzini display extender.
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    // Initialize our custom options with sensible defaults
    $options['subtitle'] = [];
    $options['suppress_headers'] = [];
    $options['move_result'] = FALSE;

    return $options;
  }

  /**
   * {@inheritdoc}
   *
   * Builds the options form for the Vizzini display extender.
   *
   * @param array $form
   *   The form array to add elements to.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
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
          // Create a descriptive label for each header to help users identify them
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
          // Disable the form if there are no headers to suppress
          '#disabled' => !$headers,
        ];

        break;
    }
  }

  /**
   * {@inheritdoc}
   *
   * Processes the submitted options form and saves the selected values.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    // Clean the form values to ensure we're working with properly formatted data
    $form_values = $form_state->cleanValues()->getValues();
    $section = $form_state->get('section');

    // Store only the values for the section being edited
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
   * Make all the vizzini options available for use throughout the view.
   *
   * This is called before the view is executed, making the settings available
   * to theme functions and other hooks that may need access to this data.
   */
  public function preExecute() {
    // Attach our options directly to the view object for easy access
    // This allows other modules and theme functions to access these settings
    $this->view->vizzini = $this->options;
  }

  /**
   * {@inheritdoc}
   *
   * Provide the default summary for options in the views UI.
   *
   * @param array $categories
   *   Categories array that can be added to or modified.
   * @param array $options
   *   Options array that views uses to display settings summaries.
   */
  public function optionsSummary(&$categories, &$options) {
    // Create a new category for our settings in the Views UI
    $categories['vizzini'] = [
      'title' => $this->t('Vizzini Settings'),
      'column' => 'second',
    ];

    // Truncate long subtitles to keep the UI clean and readable
    $subtitle = empty($this->options['subtitle'])
      ? $this->t('None')
      : $this->options['subtitle'];

    // If the subtitle is more than 4 words, truncate it with an ellipsis
    if (str_word_count($subtitle, 0) > 4) {
      $words = str_word_count($subtitle, 2);
      $pos = array_keys($words);
      $subtitle = substr($subtitle, 0, $pos[4]) . '...';
    }

    // Add summary items for each setting
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
        // Show a count of suppressed headers or "None" if none are selected
      !!($headers = array_filter($this->options['suppress_headers']))
        ? count($headers) . ' Suppressed'
        : 'None'
      )
    ];
  }
}