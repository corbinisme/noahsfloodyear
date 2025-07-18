<?php

namespace Drupal\Core\Asset;

use Drupal\Component\FileCache\FileCacheFactory;
use Drupal\Component\FileCache\FileCacheInterface;
use Drupal\Component\Serialization\Exception\InvalidDataTypeException;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Asset\Exception\IncompleteLibraryDefinitionException;
use Drupal\Core\Asset\Exception\InvalidLibrariesOverrideSpecificationException;
use Drupal\Core\Asset\Exception\InvalidLibraryFileException;
use Drupal\Core\Asset\Exception\LibraryDefinitionMissingLicenseException;
use Drupal\Core\Extension\ExtensionPathResolver;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\Core\Theme\ComponentPluginManager;
use Drupal\Core\Theme\ActiveTheme;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\Core\Plugin\Component;

/**
 * Parses library files to get extension data.
 */
class LibraryDiscoveryParser {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * The app root.
   *
   * @var string
   */
  protected $root;

  /**
   * The stream wrapper manager.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  protected $streamWrapperManager;

  /**
   * The libraries directory file finder.
   *
   * @var \Drupal\Core\Asset\LibrariesDirectoryFileFinder
   */
  protected $librariesDirectoryFileFinder;

  /**
   * The component plugin manager.
   *
   * @var \Drupal\Core\Theme\ComponentPluginManager
   */
  protected $componentPluginManager;

  /**
   * The extension path resolver.
   *
   * @var \Drupal\Core\Extension\ExtensionPathResolver
   */
  protected $extensionPathResolver;

  /**
   * The file cache.
   *
   * @var \Drupal\Component\FileCache\FileCacheInterface
   */
  protected FileCacheInterface $fileCache;

  /**
   * Constructs a new LibraryDiscoveryParser instance.
   *
   * @param string $root
   *   The app root.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   The theme manager.
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper_manager
   *   The stream wrapper manager.
   * @param \Drupal\Core\Asset\LibrariesDirectoryFileFinder $libraries_directory_file_finder
   *   The libraries directory file finder.
   * @param \Drupal\Core\Extension\ExtensionPathResolver $extension_path_resolver
   *   The extension path resolver.
   * @param \Drupal\Core\Theme\ComponentPluginManager|null $component_plugin_manager
   *   The component plugin manager.
   */
  public function __construct($root, ModuleHandlerInterface $module_handler, ThemeManagerInterface $theme_manager, StreamWrapperManagerInterface $stream_wrapper_manager, LibrariesDirectoryFileFinder $libraries_directory_file_finder, ExtensionPathResolver $extension_path_resolver, ?ComponentPluginManager $component_plugin_manager = NULL) {
    $this->root = $root;
    $this->moduleHandler = $module_handler;
    $this->themeManager = $theme_manager;
    $this->streamWrapperManager = $stream_wrapper_manager;
    $this->librariesDirectoryFileFinder = $libraries_directory_file_finder;
    $this->extensionPathResolver = $extension_path_resolver;
    $this->fileCache = FileCacheFactory::get('library_parser');
    if (!isset($component_plugin_manager)) {
      @trigger_error('Calling ' . __METHOD__ . '() without the $component_plugin_manager argument is deprecated in drupal:10.3.0 and will be required in drupal:11.0.0. See https://www.drupal.org/node/3410260', E_USER_DEPRECATED);
      $component_plugin_manager = \Drupal::service('plugin.manager.sdc');
    }
    $this->componentPluginManager = $component_plugin_manager;
  }

  /**
   * Parses and builds up all the libraries information of an extension.
   *
   * @param string $extension
   *   The name of the extension that registered a library.
   *
   * @return array
   *   All library definitions of the passed extension.
   *
   * @throws \Drupal\Core\Asset\Exception\IncompleteLibraryDefinitionException
   *   Thrown when a library has no js/css/setting.
   * @throws \UnexpectedValueException
   *   Thrown when a js file defines a positive weight.
   * @throws \UnknownExtensionTypeException
   *   Thrown when the extension type is unknown.
   * @throws \UnknownExtensionException
   *   Thrown when the extension is unknown.
   * @throws \InvalidLibraryFileException
   *   Thrown when the library file is invalid.
   * @throws \InvalidLibrariesOverrideSpecificationException
   *   Thrown when a definition refers to a non-existent library.
   * @throws \Drupal\Core\Asset\Exception\LibraryDefinitionMissingLicenseException
   *   Thrown when a library definition has no license information.
   * @throws \LogicException
   *   Thrown when a header key in a library definition is invalid.
   */
  public function buildByExtension($extension) {
    if ($extension === 'core') {
      $path = 'core';
      $extension_type = 'core';
    }
    else {
      if ($this->moduleHandler->moduleExists($extension)) {
        $extension_type = 'module';
      }
      else {
        $extension_type = 'theme';
      }
      $path = $this->extensionPathResolver->getPath($extension_type, $extension);
    }

    $libraries = $this->parseLibraryInfo($extension, $path);
    $libraries = $this->applyLibrariesOverride($libraries, $extension);

    foreach ($libraries as $id => &$library) {
      if (!isset($library['js']) && !isset($library['css']) && !isset($library['drupalSettings']) && !isset($library['dependencies'])) {
        throw new IncompleteLibraryDefinitionException(sprintf("Incomplete library definition for definition '%s' in extension '%s'", $id, $extension));
      }
      $library += ['dependencies' => [], 'js' => [], 'css' => []];

      if (isset($library['header']) && !is_bool($library['header'])) {
        throw new \LogicException(sprintf("The 'header' key in the library definition '%s' in extension '%s' is invalid: it must be a boolean.", $id, $extension));
      }

      if (isset($library['version'])) {
        // @todo Retrieve version of a non-core extension.
        if ($library['version'] === 'VERSION') {
          $library['version'] = \Drupal::VERSION;
        }
        // Remove 'v' prefix from external library versions.
        elseif (is_string($library['version']) && $library['version'][0] === 'v') {
          $library['version'] = substr($library['version'], 1);
        }
      }

      // If this is a 3rd party library, the license info is required.
      if (isset($library['remote']) && !isset($library['license'])) {
        throw new LibraryDefinitionMissingLicenseException(sprintf("Missing license information in library definition for definition '%s' extension '%s': it has a remote, but no license.", $id, $extension));
      }

      // Assign Drupal's license to libraries that don't have license info.
      if (!isset($library['license'])) {
        $library['license'] = [
          'name' => 'GPL-2.0-or-later',
          'url' => 'https://www.drupal.org/licensing/faq',
          'gpl-compatible' => TRUE,
        ];
      }

      foreach (['js', 'css'] as $type) {
        // Prepare (flatten) the SMACSS-categorized definitions.
        // @todo After Asset(ic) changes, retain the definitions as-is and
        //   properly resolve dependencies for all (css) libraries per category,
        //   and only once prior to rendering out an HTML page.
        if ($type == 'css' && !empty($library[$type])) {
          assert(static::validateCssLibrary($library[$type]) < 2, 'CSS files should be specified as key/value pairs, where the values are configuration options. See https://www.drupal.org/node/2274843.');
          assert(static::validateCssLibrary($library[$type]) === 0, 'CSS must be nested under a category. See https://www.drupal.org/node/2274843.');
          foreach ($library[$type] as $category => $files) {
            $category_weight = 'CSS_' . strtoupper($category);
            assert(defined($category_weight), 'Invalid CSS category: ' . $category . '. See https://www.drupal.org/node/2274843.');
            foreach ($files as $source => $options) {
              if (!isset($options['weight'])) {
                $options['weight'] = 0;
              }
              // Apply the corresponding weight defined by CSS_* constants.
              $options['weight'] += constant($category_weight);
              $library[$type][$source] = $options;
            }
            unset($library[$type][$category]);
          }
        }
        foreach ($library[$type] as $source => $options) {
          unset($library[$type][$source]);
          // Allow to omit the options hashmap in YAML declarations.
          if (!is_array($options)) {
            $options = [];
          }
          if ($type == 'js' && isset($options['weight']) && $options['weight'] > 0) {
            throw new \UnexpectedValueException("The $extension/$id library defines a positive weight for '$source'. Only negative weights are allowed (but should be avoided). Instead of a positive weight, specify accurate dependencies for this library.");
          }
          // Unconditionally apply default groups for the defined asset files.
          // The library system is a dependency management system. Each library
          // properly specifies its dependencies instead of relying on a custom
          // processing order.
          if ($type == 'js') {
            $options['group'] = JS_LIBRARY;
          }
          elseif ($type == 'css') {
            // Component stylesheets should be added in the "theme" aggregate
            // group to load them alongside the theme.
            // @see \Drupal\Core\Plugin\Component::getLibraryName
            $options['group'] = ($extension_type == 'theme' || str_starts_with($id, 'components.')) ? CSS_AGGREGATE_THEME : CSS_AGGREGATE_DEFAULT;
          }
          // By default, all library assets are files.
          if (!isset($options['type'])) {
            $options['type'] = 'file';
          }
          if ($options['type'] == 'external') {
            $options['data'] = $source;
          }
          // Determine the file asset URI.
          else {
            if ($source[0] === '/') {
              // An absolute path maps to DRUPAL_ROOT / base_path().
              if ($source[1] !== '/') {
                $source = substr($source, 1);
                // Non core provided libraries can be in multiple locations.
                if (str_starts_with($source, 'libraries/')) {
                  $path_to_source = $this->librariesDirectoryFileFinder->find(substr($source, 10));
                  if ($path_to_source) {
                    $source = $path_to_source;
                  }
                }
                $options['data'] = $source;
              }
              // A protocol-free URI (e.g., //cdn.com/example.js) is external.
              else {
                $options['type'] = 'external';
                $options['data'] = $source;
              }
            }
            // A stream wrapper URI (e.g., public://generated_js/example.js).
            elseif ($this->streamWrapperManager->isValidUri($source)) {
              $options['data'] = $source;
            }
            // A regular URI (e.g., http://example.com/example.js) without
            // 'external' explicitly specified, which may happen if, e.g.
            // libraries-override is used.
            elseif ($this->isValidUri($source)) {
              $options['type'] = 'external';
              $options['data'] = $source;
            }
            // By default, file paths are relative to the registering extension.
            else {
              $options['data'] = $path . '/' . $source;
            }
          }

          if (!isset($library['version'])) {
            // @todo Get the information from the extension.
            $options['version'] = -1;
          }
          else {
            $options['version'] = $library['version'];
          }

          // Set the 'minified' flag on JS file assets, default to FALSE.
          if ($type == 'js' && $options['type'] == 'file') {
            $options['minified'] = $options['minified'] ?? FALSE;
          }

          $library[$type][] = $options;
        }
      }
    }

    return $libraries;
  }

  /**
   * Parses a given library file and allows modules and themes to alter it.
   *
   * This method sets the parsed information onto the library property.
   *
   * Library information is parsed from *.libraries.yml files; see
   * editor.libraries.yml for an example. Every library must have at least one
   * js or css entry. Each entry starts with a machine name and defines the
   * following elements:
   * - js: A list of JavaScript files to include. Each file is keyed by the file
   *   path. An item can have several attributes (like HTML
   *   attributes). For example:
   *   @code
   *   js:
   *     path/js/file.js: { attributes: { defer: true } }
   *   @endcode
   *   If the file has no special attributes, just use an empty object:
   *   @code
   *   js:
   *     path/js/file.js: {}
   *   @endcode
   *   The path of the file is relative to the module or theme directory, unless
   *   it starts with a /, in which case it is relative to the Drupal root. If
   *   the file path starts with //, it will be treated as a protocol-free,
   *   external resource (e.g., //cdn.com/library.js). Full URLs
   *   (e.g., http://cdn.com/library.js) as well as URLs that use a valid
   *   stream wrapper (e.g., public://path/to/file.js) are also supported.
   * - css: A list of categories for which the library provides CSS files. The
   *   available categories are:
   *   - base
   *   - layout
   *   - component
   *   - state
   *   - theme
   *   Each category is itself a key for a sub-list of CSS files to include:
   *   @code
   *   css:
   *     component:
   *       css/file.css: {}
   *   @endcode
   *   Just like with JavaScript files, each CSS file is the key of an object
   *   that can define specific attributes. The format of the file path is the
   *   same as for the JavaScript files.
   *   If the JavaScript or CSS file starts with /libraries/ the
   *   library.libraries_directory_file_finder service is used to find the files
   *   in the following locations:
   *   - A libraries directory in the current site directory, for example:
   *     sites/default/libraries.
   *   - The root libraries directory.
   *   - A libraries directory in the selected installation profile, for
   *     example: profiles/my_install_profile/libraries.
   * - dependencies: A list of libraries this library depends on.
   * - version: The library version. The string "VERSION" can be used to mean
   *   the current Drupal core version.
   * - header: By default, JavaScript files are included in the footer. If the
   *   script must be included in the header (along with all its dependencies),
   *   set this to true. Defaults to false.
   * - minified: If the file is already minified, set this to true to avoid
   *   minifying it again. Defaults to false.
   * - remote: If the library is a third-party script, this provides the
   *   repository URL for reference.
   * - license: If the remote property is set, the license information is
   *   required. It has 3 properties:
   *   - name: A System Package Data Exchange (SPDX) license identifier such as
   *     "GPL-2.0-or-later" (see https://spdx.org/licenses/), or if not
   *     applicable, the human-readable name of the license.
   *   - url: The URL of the license file/information for the version of the
   *     library used.
   *   - gpl-compatible: A Boolean for whether this library is GPL compatible.
   *
   * See https://www.drupal.org/node/2274843#define-library for more
   * information.
   *
   * @param string $extension
   *   The name of the extension that registered a library.
   * @param string $path
   *   The relative path to the extension.
   *
   * @return array
   *   An array of parsed library data.
   *
   * @throws \Drupal\Core\Asset\Exception\InvalidLibraryFileException
   *   Thrown when a parser exception got thrown.
   */
  protected function parseLibraryInfo($extension, $path) {
    $libraries = [];

    $library_file = $path . '/' . $extension . '.libraries.yml';
    $library_path = $this->root . '/' . $library_file;

    if (file_exists($library_path)) {
      $libraries = $this->fileCache->get($library_path);
      if ($libraries === NULL) {
        try {
          $libraries = Yaml::decode(file_get_contents($this->root . '/' . $library_file)) ?? [];
          $this->fileCache->set($library_path, $libraries);
        }
        catch (InvalidDataTypeException $e) {
          // Rethrow a more helpful exception to provide context.
          throw new InvalidLibraryFileException(sprintf('Invalid library definition in %s: %s', $library_file, $e->getMessage()), 0, $e);
        }
      }
    }
    // Core also provides additional libraries that don't come from the YAML,
    // file nor the hook_library_info_build. They come from single-directory
    // component definitions.
    $additional_libraries = $extension === 'core'
      ? $this->librariesForComponents()
      : [];
    $libraries = array_merge($additional_libraries, $libraries);

    // Allow modules to add dynamic library definitions.
    $hook = 'library_info_build';
    if ($this->moduleHandler->hasImplementations($hook, $extension)) {
      $libraries = NestedArray::mergeDeep($libraries, $this->moduleHandler->invoke($extension, $hook));
    }

    // Allow modules to alter the module's registered libraries.
    $this->moduleHandler->alter('library_info', $libraries, $extension);
    $this->themeManager->alter('library_info', $libraries, $extension);

    return $libraries;
  }

  /**
   * Apply overrides to files that have moved.
   *
   * @param array $library
   *   The library definition.
   * @param string $library_name
   *   The library name.
   * @param string $extension
   *   The extension name.
   * @param array $overrides
   *   The library overrides.
   * @param Drupal\Core\Theme\ActiveTheme $active_theme
   *   The active theme.
   *
   * @return array
   *   The modified library overrides.
   */
  protected function applyLibrariesMovedOverrides(array $library, string $library_name, string $extension, array $overrides, ActiveTheme $active_theme): array {
    if (!isset($library['moved_files'])) {
      return $overrides;
    }
    foreach ($library['moved_files'] as $old_library_name => $moved_files) {
      $deprecation_version = $moved_files['deprecation_version'];
      $removed_version = $moved_files['removed_version'];
      $deprecation_link = $moved_files['deprecation_link'];
      if (isset($overrides[$old_library_name]['css']) && isset($moved_files['css'])) {
        foreach ($overrides[$old_library_name]['css'] as $key => $files) {
          foreach ($files as $original => $target) {
            if (isset($moved_files['css'][$key][$original])) {
              $new_key = array_key_first($moved_files['css'][$key][$original]);
              $new_file = $moved_files['css'][$key][$original][$new_key];
              $theme_name = $active_theme->getName();
              // phpcs:ignore
              @trigger_error("Targeting $old_library_name $original from $theme_name library_overrides is deprecated in $deprecation_version and will be removed in $removed_version. Target $extension/$library_name $new_file instead. See $deprecation_link", E_USER_DEPRECATED);
              $overrides[$extension . '/' . $library_name]['css'][$new_key][$new_file] = $target;
            }
          }
        }
      }
      if (isset($overrides[$old_library_name]['js']) && isset($moved_files['js'])) {
        foreach ($overrides[$old_library_name]['js'] as $original => $target) {
          if (isset($moved_files['js'][$original])) {
            $new_file = $moved_files['js'][$original];
            $theme_name = $active_theme->getName();
            // phpcs:ignore
            @trigger_error("Targeting $old_library_name $original from $theme_name library_overrides is deprecated in $deprecation_version and will be removed in $removed_version. Target $extension/$library_name $new_file instead. See $deprecation_link", E_USER_DEPRECATED);
            $overrides[$extension . '/' . $library_name]['js'][$new_file] = $target;
          }
        }
      }
    }
    return $overrides;
  }

  /**
   * Builds the dynamic library definitions for single-directory components.
   *
   * @return array
   *   The core library definitions for Single-Directory Components.
   */
  protected function librariesForComponents(): array {
    // Iterate over all the components to get the CSS and JS files.
    $components = $this->componentPluginManager->getAllComponents();
    $libraries = array_reduce(
      $components,
      static function (array $libraries, Component $component) {
        $library = $component->library;
        if (empty($library)) {
          return $libraries;
        }
        $library_name = $component->getLibraryName();
        [, $library_id] = explode('/', $library_name);
        return array_merge($libraries, [$library_id => $library]);
      },
      []
    );
    $libraries['components.all'] = [
      'dependencies' => array_map(
        static fn(Component $component) => $component->getLibraryName(),
        $components
      ),
    ];
    return $libraries;
  }

  /**
   * Apply libraries overrides specified for the current active theme.
   *
   * @param array $libraries
   *   The libraries definitions.
   * @param string $extension
   *   The extension in which these libraries are defined.
   *
   * @return array
   *   The modified libraries definitions.
   */
  protected function applyLibrariesOverride($libraries, $extension) {
    $active_theme = $this->themeManager->getActiveTheme();
    // ActiveTheme::getLibrariesOverride() returns libraries-overrides for the
    // current theme as well as all its base themes.
    $all_libraries_overrides = $active_theme->getLibrariesOverride();
    foreach ($all_libraries_overrides as $theme_path => $libraries_overrides) {
      foreach ($libraries as $library_name => $library) {
        $libraries_overrides = $this->applyLibrariesMovedOverrides($library, $library_name, $extension, $libraries_overrides, $active_theme);

        // Process libraries overrides.
        if (isset($libraries_overrides["$extension/$library_name"])) {
          if (isset($library['deprecated'])) {
            $override_message = sprintf('Theme "%s" is overriding a deprecated library.', $extension);
            $library_deprecation = str_replace('%library_id%', "$extension/$library_name", $library['deprecated']);
            // phpcs:ignore Drupal.Semantics.FunctionTriggerError
            @trigger_error("$override_message $library_deprecation", E_USER_DEPRECATED);
          }
          // Active theme defines an override for this library.
          $override_definition = $libraries_overrides["$extension/$library_name"];
          if (is_string($override_definition) || $override_definition === FALSE) {
            // A string or boolean definition implies an override (or removal)
            // for the whole library. Use the override key to specify that this
            // library will be overridden when it is called.
            // @see \Drupal\Core\Asset\LibraryDiscovery::getLibraryByName()
            if ($override_definition) {
              $libraries[$library_name]['override'] = $override_definition;
            }
            else {
              $libraries[$library_name]['override'] = FALSE;
            }
          }
          elseif (is_array($override_definition)) {
            // An array definition implies an override for an asset within this
            // library.
            foreach ($override_definition as $sub_key => $value) {
              // Throw an exception if the asset is not properly specified.
              if (!is_array($value)) {
                throw new InvalidLibrariesOverrideSpecificationException(sprintf('Library asset %s is not correctly specified. It should be in the form "extension/library_name/sub_key/path/to/asset.js".', "$extension/$library_name/$sub_key"));
              }
              if ($sub_key === 'drupalSettings') {
                // drupalSettings may not be overridden.
                throw new InvalidLibrariesOverrideSpecificationException(sprintf('drupalSettings may not be overridden in libraries-override. Trying to override %s. Use hook_library_info_alter() instead.', "$extension/$library_name/$sub_key"));
              }
              elseif ($sub_key === 'css') {
                // SMACSS category should be incorporated into the asset name.
                foreach ($value as $category => $overrides) {
                  $this->setOverrideValue($libraries[$library_name], [$sub_key, $category], $overrides, $theme_path);
                }
              }
              else {
                $this->setOverrideValue($libraries[$library_name], [$sub_key], $value, $theme_path);
              }
            }
          }
        }
      }
    }

    return $libraries;
  }

  /**
   * Determines if the supplied string is a valid URI.
   */
  protected function isValidUri($string) {
    return count(explode('://', $string)) === 2;
  }

  /**
   * Overrides the specified library asset.
   *
   * @param array $library
   *   The containing library definition.
   * @param array $sub_key
   *   An array containing the sub-keys specifying the library asset, e.g.
   *   ['js'] or ['css', 'component'].
   * @param array $overrides
   *   Specifies the overrides, this is an array where the key is the asset to
   *   be overridden while the value is overriding asset.
   * @param string $theme_path
   *   The theme or base theme.
   */
  protected function setOverrideValue(array &$library, array $sub_key, array $overrides, $theme_path) {
    foreach ($overrides as $original => $replacement) {
      // Get the attributes of the asset to be overridden. If the key does
      // not exist, then throw an exception.
      $key_exists = NULL;
      $parents = array_merge($sub_key, [$original]);
      // Save the attributes of the library asset to be overridden.
      $attributes = NestedArray::getValue($library, $parents, $key_exists);
      if ($key_exists) {
        // Remove asset to be overridden.
        NestedArray::unsetValue($library, $parents);
        // No need to replace if FALSE is specified, since that is a removal.
        if ($replacement) {
          // Ensure the replacement path is relative to drupal root.
          $replacement = $this->resolveThemeAssetPath($theme_path, $replacement);
          $new_parents = array_merge($sub_key, [$replacement]);
          // Replace with an override if specified.
          NestedArray::setValue($library, $new_parents, $attributes);
        }
      }
    }
  }

  /**
   * Ensures that a full path is returned for an overriding theme asset.
   *
   * @param string $theme_path
   *   The theme or base theme.
   * @param string $overriding_asset
   *   The overriding library asset.
   *
   * @return string
   *   A fully resolved theme asset path relative to the Drupal directory.
   */
  protected function resolveThemeAssetPath($theme_path, $overriding_asset) {
    if ($overriding_asset[0] !== '/' && !$this->isValidUri($overriding_asset)) {
      // The destination is not an absolute path and it's not a URI (e.g.
      // public://generated_js/example.js or http://example.com/js/my_js.js), so
      // it's relative to the theme.
      return '/' . $theme_path . '/' . $overriding_asset;
    }
    return $overriding_asset;
  }

  /**
   * Validates CSS library structure.
   *
   * @param array $library
   *   The library definition array.
   *
   * @return int
   *   Returns based on validity:
   *     - 0 if the library definition is valid
   *     - 1 if the library definition has improper nesting
   *     - 2 if the library definition specifies files as an array
   */
  public static function validateCssLibrary($library) {
    $categories = [];
    // Verify options first and return early if invalid.
    foreach ($library as $category => $files) {
      if (!is_array($files)) {
        return 2;
      }
      $categories[] = $category;
      foreach ($files as $options) {
        if (!is_array($options)) {
          return 1;
        }
      }
    }

    return 0;
  }

}
