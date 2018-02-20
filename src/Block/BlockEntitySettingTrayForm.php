<?php

namespace Drupal\settings_tray_translations\Block;

use Drupal\config_translation\Exception\ConfigMapperLanguageException;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Url;
use Drupal\settings_tray\Block\BlockEntitySettingTrayForm as CoreSettingsTrayForm;

/**
 * Extends the Settings Tray block entity form to handle translations.
 *
 * @internal
 */
class BlockEntitySettingTrayForm extends CoreSettingsTrayForm {

  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $this->addTranslationElements($form, $form_state);
    return $form;
  }


  /**
   * Add translation related elements if needed.
   *
   * @todo This code is mostly copied from
   *   \Drupal\config_translation\Controller\ConfigTranslationController
   *   clean up.
   *
   * @param $form
   * @param $form_state
   */
  protected function addTranslationElements(&$form, $form_state) {
    if (!\Drupal::moduleHandler()->moduleExists('config_translation')) {
      return;
    }

    $lang_manager = \Drupal::languageManager();
    $languages = $lang_manager->getLanguages();
    if (count($languages) == 1) {
      // No need to translate.
      return;
    }

    $current_lang = \Drupal::request()->get('config_lang');
    $mapper = $this->getConfigEntityMapper($current_lang);

    try {
      $original_langcode = $mapper->getLangcode();
      $operations_access = TRUE;
    }
    catch (ConfigMapperLanguageException $exception) {
      $items = [];
      foreach ($mapper->getConfigNames() as $config_name) {
        $langcode = $mapper->getLangcodeFromConfig($config_name);
        $items[] = $this->t('@name: @langcode', [
          '@name' => $config_name,
          '@langcode'  => $langcode,
        ]);
      }
      $message = [
        'message' => ['#markup' => $this->t('The configuration objects have different language codes so they cannot be translated:')],
        'items' => [
          '#theme' => 'item_list',
          '#items' => $items,
        ],
      ];
      drupal_set_message($message, 'warning');

      return;
    }

    // We create a fake request object to pass into
    // ConfigMapperInterface::populateFromRouteMatch() for the different languages.
    // Creating a separate request for each language and route is neither easily
    // possible nor performant.
    $fake_request = \Drupal::request()->duplicate();

    $current_lang = $lang_manager->getLanguage($current_lang);

    // This is needed because
    // ConfigMapperInterface::getAddRouteParameters(), for example,
    // needs to return the correct language code for each table row.
    $fake_route_match = RouteMatch::createFromRequest($fake_request);

    $viewing_translation = FALSE;
    $query = \Drupal::destination()->getAsArray();
    if ($mapper->hasTranslation($current_lang)) {
      $viewing_translation = TRUE;


      $form['translation_edit'] = [
        '#type' => 'link',
        '#title' => $this->t('This page is currently using the @lang translation.', ['@lang' => $current_lang->getName()]),
        '#url' => Url::fromRoute($mapper->getEditRouteName(), $mapper->getEditRouteParameters(), ['query' => $query]),
        '#attributes' => $this->getOffCanvasAttributes(),
      ];
      $form['translation_edit']['#weight'] = -1200;
      $form['translation_form'] = \Drupal::formBuilder()->getForm('\Drupal\config_translation\Form\ConfigTranslationEditForm', $fake_route_match, $mapper->getPluginId(), $current_lang->getId());
      $form['translation_form']['#weight'] = -1000;
      unset($form['translation_form']['actions']);
    }

    $language_rows = [];
    foreach ($languages as $language) {
      $langcode = $language->getId();
      if ($langcode == $current_lang->getId() && $viewing_translation) {
        continue;
      }


      $mapper->populateFromRouteMatch($fake_route_match);
      $mapper->setLangcode($langcode);

      // Prepare the language name and the operations depending on whether this
      // is the original language or not.
      if ($langcode !== $original_langcode) {
        $language_name = $language->getName();

        $operations = [];
        // If no translation exists for this language, link to add one.
        if (!$mapper->hasTranslation($language)) {
          $operations['add'] = [
            'title' => $this->t('Add'),
            'url' => Url::fromRoute($mapper->getAddRouteName(), $mapper->getAddRouteParameters(), ['query' => $query]),
            'attributes' => $this->getOffCanvasAttributes(),
          ];
        }
        else {
          // Otherwise, link to edit the existing translation.
          $operations['edit'] = [
            'title' => $this->t('Edit'),
            'url' => Url::fromRoute($mapper->getEditRouteName(), $mapper->getEditRouteParameters(), ['query' => $query]),
            'attributes' => $this->getOffCanvasAttributes(),
          ];

          $operations['delete'] = [
            'title' => $this->t('Delete'),
            'url' => Url::fromRoute($mapper->getDeleteRouteName(), $mapper->getDeleteRouteParameters(), ['query' => $query]),
            'attributes' => $this->getOffCanvasAttributes(),
          ];
        }
        $language_rows[$langcode]['language'] = [
          '#markup' => $language_name,
        ];

        $language_rows[$langcode]['operations'] = [
          '#type' => 'operations',
          '#links' => $operations,
          // Even if the mapper contains multiple language codes, the source
          // configuration can still be edited.
          '#access' => ($langcode == $original_langcode) || $operations_access,
        ];
      }


    }
    if ($language_rows) {

      $form['languages_details'] = [
        '#type' => 'details',
        '#title' => $viewing_translation ? $this->t('Other Translations') : $this->t('Translations'),
        '#open' => FALSE    ,
      ];

      $form['languages_details']['languages'] = [
          '#type' => 'table',
          '#header' => [$this->t('Language'), $this->t('Operations')],
        ] + $language_rows;
    }
  }

  /**
   * Get the translation config mapper for the block
   *
   * @param $current_lang
   * @return \Drupal\config_translation\ConfigEntityMapper
   */
  protected function getConfigEntityMapper($current_lang) {
    /** @var \Drupal\config_translation\ConfigEntityMapper $mapper */
    $mapper = \Drupal::service('plugin.manager.config_translation.mapper')
      ->createInstance('block');
    $mapper->setLangcode($current_lang);
    $mapper->setEntity($this->entity);
    return $mapper;
  }

  /**
   * Get attributes for off-canvas links.
   *
   * @todo Move this in a utility class so .module file can use it.
   *
   * @return array
   *   The attributes.
   */
  protected function getOffCanvasAttributes() {
    return [
      'class' => ['use-ajax'],
      'data-dialog-type' => 'dialog',
      'data-dialog-renderer' => 'off_canvas',
    ];
  }

}
