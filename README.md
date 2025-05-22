# Vizzini Tweaks: Extending Views With Custom Settings

## Overview

The Vizzini Tweaks module demonstrates how to extend Drupal Views using the `DisplayExtenderPluginBase` class. This powerful but under-documented feature allows you to add custom settings to Drupal Views that can be configured by site administrators without requiring custom code for each view instance.

This module was created as a [demonstration for DrupalGovCon 2023](https://drupal.tv/events/drupal-govcon/drupal-govcon-2023/extending-views-custom-settings), showcasing how to approach the problem of extending Views functionality in a reusable way. 

While this module provides several example implementations, it's designed primarily as a learning tool. The documentation and code examples serve as a foundation for developers to implement their own custom view extensions beyond what's included here. By understanding the patterns demonstrated, you'll be able to create entirely new view extensions tailored to your specific project requirements.

Why Vizzini? I mean... It would be inconceivable to call it anything else. (Also, this name matches the aesthetic/theme of the presentation this code supports.)

## Use Cases

This approach is ideal for:

- Adding configurable JavaScript to specific views
- Modifying the display of view components based on user settings
- Adding metadata or additional UI elements to views
- Altering the behavior of views conditionally

## Features

The module provides three example view extensions:

1. **Custom Subtitles**: Add configurable subtitles to Views exposed forms
2. **Result Block Repositioning**: Move the results summary from the header to between the exposed form and view results
3. **Header Suppression**: Selectively hide header elements when results are displayed

## Installation

1. Install the module like any standard Drupal module
2. The module automatically enables the `vizzini_display_extender` plugin during installation

## Usage

After installation, the Vizzini Settings will be available in the Views UI:

1. Edit any view
2. Look for the "Vizzini Settings" section in the right column of the Views UI
3. Configure the available options:
    - Subtitle
    - Move Result Block
    - Suppressed Headers

## How It Works

The module uses Drupal's `DisplayExtenderPluginBase` to add custom configuration options to Views. When these options are saved, they become available to hook implementations and templates.

### Core Components

#### 1. Plugin Definition

```php
/**
 * @ViewsDisplayExtender(
 *   id = "vizzini_display_extender",
 *   title = @Translation("Vizzini Display Extender"),
 *   help = @Translation("Extra settings for this view."),
 *   no_ui = FALSE
 * )
 */
```

This annotation registers our display extender plugin with Views.

#### 2. Option Definition

```php
protected function defineOptions() {
  $options = parent::defineOptions();

  $options['subtitle'] = [];
  $options['suppress_headers'] = [];
  $options['move_result'] = FALSE;

  return $options;
}
```

This method defines what configuration options our plugin provides.

#### 3. Options Form

The `buildOptionsForm()` method creates the UI for configuring these options in the Views admin interface. Each option (subtitle, move_result, suppress_headers) has its own section in the form.

#### 4. Options Summary

The `optionsSummary()` method provides a summary of the configured options in the Views UI overview.

#### 5. Hook Implementations

The module includes several hook implementations that use the configured options:

- `vizzini_tweaks_form_alter()`: Adds the subtitle to exposed forms
- `vizzini_tweaks_views_pre_render()`: Moves the result block
- `vizzini_tweaks_views_pre_view()`: Suppresses headers

## Creating Your Own Display Extender

To create your own display extender, follow these steps:

1. **Create the Plugin Class**:
    - Create a PHP class that extends `DisplayExtenderPluginBase`
    - Place it in `src/Plugin/views/display_extender/`
    - Add the `@ViewsDisplayExtender` annotation

2. **Define Options**:
    - Implement `defineOptions()` to specify what can be configured
    - These become the settings available in the Views UI

3. **Build the Form**:
    - Implement `buildOptionsForm()` to create the configuration UI
    - Implement `submitOptionsForm()` to save the configuration

4. **Create the Summary**:
    - Implement `optionsSummary()` to show a summary of settings in the Views UI

5. **Activate the Plugin**:
    - Add an install hook that adds your plugin ID to the `views.settings` configuration

6. **Use the Settings**:
    - Create hook implementations that check for and use your settings
    - A helper function like `vizzini_tweaks_view_settings()` can simplify access to the settings

## Design Decisions

The Vizzini Tweaks module demonstrates several best practices:

1. **Reusability**: Rather than creating one-off modifications for specific views, the module provides a framework that can be applied to any view.

2. **Separation of Concerns**: The plugin defines configuration options, while hook implementations handle the actual modifications to the view output.

3. **User Experience**: Administrative options are placed logically in the Views UI, making them discoverable and usable for site builders.

4. **Graceful Degradation**: All code checks for the existence of settings before attempting to use them, preventing errors.

5. **Clean Code**: The helper function `vizzini_tweaks_view_settings()` extracts common logic for checking if a view has custom settings, following the DRY (Don't Repeat Yourself) principle.

## Resources

- [Presentation at Drupal Gov Con 2023](https://drupal.tv/events/drupal-govcon/drupal-govcon-2023/extending-views-custom-settings)
- [Drupal Views API Documentation](https://api.drupal.org/api/drupal/core%21modules%21views%21views.api.php/group/views_hooks/10)
- [DisplayExtenderPluginBase API](https://api.drupal.org/api/drupal/core%21modules%21views%21src%21Plugin%21views%21display_extender%21DisplayExtenderPluginBase.php/class/DisplayExtenderPluginBase/10)