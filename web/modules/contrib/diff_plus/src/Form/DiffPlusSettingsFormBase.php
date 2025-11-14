<?php

namespace Drupal\diff_plus\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for diff_plus settings forms.
 */
abstract class DiffPlusSettingsFormBase extends FormBase {

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->configFactory = $container->get('config.factory');
    $instance->messenger = $container->get('messenger');
    return $instance;
  }

  /**
   * Gets the default configuration to display within this form.
   *
   * @return array
   *   The default configuration to display within this form.
   */
  protected function defaultConfiguration() {
    return [
      'enhance_diff_ui' => TRUE,
      'raw_html_indent_size' => 2,
      'raw_html_preserve_newlines' => FALSE,
      'raw_html_wrap_line_length' => 0,
      'raw_html_render_anonymously' => TRUE,
      'raw_html_strip_contextual_links' => TRUE,
      'raw_html_strip_js_view_dom_id' => TRUE,
      'raw_html_strip_comments' => TRUE,
      'raw_html_highlight_style' => '',
      'visual_html5_preserve_inline_styles' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'diff_plus';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $default_values = $this->defaultConfiguration();

    $form['ui'] = [
      '#type' => 'details',
      '#title' => $this->t('User interface'),
      '#open' => TRUE,
    ];

    $form['ui']['enhance_diff_ui'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use an alternative modern design'),
      '#description' => $this->t('The new design includes a warmer look and feel and a more minimalist user experience.'),
      '#default_value' => $default_values['enhance_diff_ui'],
    ];

    $form['visual_html5'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Customize the <em>Visual Inline (HTML5) diff display'),
    ];

    $form['visual_html5']['visual_html5_preserve_inline_styles'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Preserve inline CSS styles'),
      '#default_value' => $default_values['visual_html5_preserve_inline_styles'],
    ];

    $form['raw_html'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Customize the <em>Raw HTML</em> diff display'),
    ];

    $form['raw_html']['normalizer'] = [
      '#type' => 'details',
      '#title' => $this->t('Normalizer Settings'),
      '#open' => TRUE,
    ];

    $form['raw_html']['normalizer']['raw_html_render_anonymously'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Render content as an anonymous user'),
      '#description' => $this->t('Rendering content as an authenticated user may cause false positives.'),
      '#default_value' => $default_values['raw_html_render_anonymously'],
    ];

    $form['raw_html']['normalizer']['raw_html_strip_contextual_links'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Strip contextual links from rendered content'),
      '#description' => $this->t('Contextual links cause false-positives, it is recommended to strip them.'),
      '#default_value' => $default_values['raw_html_strip_contextual_links'],
    ];

    $form['raw_html']['normalizer']['raw_html_strip_js_view_dom_id'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Strip "js-view-dom-id-*" classing from rendered content'),
      '#description' => $this->t('This class can cause false-positives, it is recommended to strip this class.'),
      '#default_value' => $default_values['raw_html_strip_js_view_dom_id'],
    ];

    $form['raw_html']['normalizer']['raw_html_strip_comments'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Strip HTML comments from rendered content'),
      '#default_value' => $default_values['raw_html_strip_comments'],
    ];

    $form['raw_html']['beautifier'] = [
      '#type' => 'details',
      '#title' => $this->t('Beautifier Settings'),
      '#open' => FALSE,
    ];

    $form['raw_html']['beautifier']['raw_html_indent_size'] = [
      '#type' => 'number',
      '#title' => $this->t('Indentation size'),
      '#description' => $this->t('The number of spaces that nested elements are indented.'),
      '#min' => 0,
      '#default_value' => $default_values['raw_html_indent_size'],
    ];

    $form['raw_html']['beautifier']['raw_html_wrap_line_length'] = [
      '#type' => 'number',
      '#title' => $this->t('Wrap line length'),
      '#description' => $this->t('Maximum number of characters per line (enter 0 to disable).'),
      '#min' => 0,
      '#default_value' => $default_values['raw_html_wrap_line_length'],
    ];

    $form['raw_html']['beautifier']['raw_html_preserve_newlines'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Preserve newlines'),
      '#description' => $this->t('If enabled, line breaks will be preserved.'),
      '#default_value' => $default_values['raw_html_preserve_newlines'],
    ];

    $form['raw_html']['highlight'] = [
      '#type' => 'details',
      '#title' => $this->t('Highlighter Settings'),
    ];

    $form['raw_html']['highlight']['raw_html_highlight_style'] = [
      '#type' => 'select',
      '#title' => $this->t('Highlight style'),
      '#description' => $this->t('Warning!  Some highlight styles may not be fully accessible!'),
      '#empty_option' => $this->t('Default'),
      '#options' => [
        'a11y-dark.min.css' => $this->t('a11y dark'),
        'a11y-light.min.css' => $this->t('a11y light'),
        'agate.min.css' => $this->t('Agate'),
        'an-old-hope.min.css' => $this->t('An Old Hope'),
        'androidstudio.min.css' => $this->t('Androidstudio'),
        'arduino-light.min.css' => $this->t('Arduino Light'),
        'arta.min.css' => $this->t('Arta'),
        'ascetic.min.css' => $this->t('Ascetic'),
        'atom-one-dark-reasonable.min.css' => $this->t('Atom One Dark Reasonable'),
        'atom-one-dark.min.css' => $this->t('Atom One Dark'),
        'atom-one-light.min.css' => $this->t('Atom One Light'),
        'base16/3024.min.css' => $this->t('Base16 / 3024'),
        'base16/apathy.min.css' => $this->t('Base16 / Apathy'),
        'base16/apprentice.min.css' => $this->t('Base16 / Apprentice'),
        'base16/ashes.min.css' => $this->t('Base16 / Ashes'),
        'base16/atelier-cave-light.min.css' => $this->t('Base16 / Atelier Cave'),
        'base16/atelier-cave.min.css' => $this->t('Base16 / Atelier Cave Light'),
        'base16/atelier-dune-light.min.css' => $this->t('Base16 / Atelier Dune'),
        'base16/atelier-dune.min.css' => $this->t('Base16 / Atelier Dune Light'),
        'base16/atelier-estuary-light.min.css' => $this->t('Base16 / Atelier Estuary'),
        'base16/atelier-estuary.min.css' => $this->t('Base16 / Atelier Estuary Light'),
        'base16/atelier-forest-light.min.css' => $this->t('Base16 / Atelier Forest'),
        'base16/atelier-forest.min.css' => $this->t('Base16 / Atelier Forest Light'),
        'base16/atelier-heath-light.min.css' => $this->t('Base16 / Atelier Heath'),
        'base16/atelier-heath.min.css' => $this->t('Base16 / Atelier Heath Light'),
        'base16/atelier-lakeside-light.min.css' => $this->t('Base16 / Atelier Lakeside'),
        'base16/atelier-lakeside.min.css' => $this->t('Base16 / Atelier Lakeside Light'),
        'base16/atelier-plateau-light.min.css' => $this->t('Base16 / Atelier Plateau'),
        'base16/atelier-plateau.min.css' => $this->t('Base16 / Atelier Plateau Light'),
        'base16/atelier-savanna-light.min.css' => $this->t('Base16 / Atelier Savanna'),
        'base16/atelier-savanna.min.css' => $this->t('Base16 / Atelier Savanna Light'),
        'base16/atelier-seaside-light.min.css' => $this->t('Base16 / Atelier Seaside'),
        'base16/atelier-seaside.min.css' => $this->t('Base16 / Atelier Seaside Light'),
        'base16/atelier-sulphurpool-light.min.css' => $this->t('Base16 / Atelier Sulphurpool'),
        'base16/atelier-sulphurpool.min.css' => $this->t('Base16 / Atelier Sulphurpool Light'),
        'base16/atlas.min.css' => $this->t('Base16 / Atlas'),
        'base16/bespin.min.css' => $this->t('Base16 / Bespin'),
        'base16/black-metal-bathory.min.css' => $this->t('Base16 / Black Metal'),
        'base16/black-metal-burzum.min.css' => $this->t('Base16 / Black Metal Bathory'),
        'base16/black-metal-dark-funeral.min.css' => $this->t('Base16 / Black Metal Burzum'),
        'base16/black-metal-gorgoroth.min.css' => $this->t('Base16 / Black Metal Dark Funeral'),
        'base16/black-metal-immortal.min.css' => $this->t('Base16 / Black Metal Gorgoroth'),
        'base16/black-metal-khold.min.css' => $this->t('Base16 / Black Metal Immortal'),
        'base16/black-metal-marduk.min.css' => $this->t('Base16 / Black Metal Khold'),
        'base16/black-metal-mayhem.min.css' => $this->t('Base16 / Black Metal Marduk'),
        'base16/black-metal-nile.min.css' => $this->t('Base16 / Black Metal Mayhem'),
        'base16/black-metal-venom.min.css' => $this->t('Base16 / Black Metal Nile'),
        'base16/black-metal.min.css' => $this->t('Base16 / Black Metal Venom'),
        'base16/brewer.min.css' => $this->t('Base16 / Brewer'),
        'base16/bright.min.css' => $this->t('Base16 / Bright'),
        'base16/brogrammer.min.css' => $this->t('Base16 / Brogrammer'),
        'base16/brush-trees-dark.min.css' => $this->t('Base16 / Brush Trees'),
        'base16/brush-trees.min.css' => $this->t('Base16 / Brush Trees Dark'),
        'base16/chalk.min.css' => $this->t('Base16 / Chalk'),
        'base16/circus.min.css' => $this->t('Base16 / Circus'),
        'base16/classic-dark.min.css' => $this->t('Base16 / Classic Dark'),
        'base16/classic-light.min.css' => $this->t('Base16 / Classic Light'),
        'base16/codeschool.min.css' => $this->t('Base16 / Codeschool'),
        'base16/colors.min.css' => $this->t('Base16 / Colors'),
        'base16/cupcake.min.css' => $this->t('Base16 / Cupcake'),
        'base16/cupertino.min.css' => $this->t('Base16 / Cupertino'),
        'base16/danqing.min.css' => $this->t('Base16 / Danqing'),
        'base16/darcula.min.css' => $this->t('Base16 / Darcula'),
        'base16/dark-violet.min.css' => $this->t('Base16 / Dark Violet'),
        'base16/darkmoss.min.css' => $this->t('Base16 / Darkmoss'),
        'base16/darktooth.min.css' => $this->t('Base16 / Darktooth'),
        'base16/decaf.min.css' => $this->t('Base16 / Decaf'),
        'base16/default-dark.min.css' => $this->t('Base16 / Default Dark'),
        'base16/default-light.min.css' => $this->t('Base16 / Default Light'),
        'base16/dirtysea.min.css' => $this->t('Base16 / Dirtysea'),
        'base16/dracula.min.css' => $this->t('Base16 / Dracula'),
        'base16/edge-dark.min.css' => $this->t('Base16 / Edge Dark'),
        'base16/edge-light.min.css' => $this->t('Base16 / Edge Light'),
        'base16/eighties.min.css' => $this->t('Base16 / Eighties'),
        'base16/embers.min.css' => $this->t('Base16 / Embers'),
        'base16/equilibrium-dark.min.css' => $this->t('Base16 / Equilibrium Dark'),
        'base16/equilibrium-gray-dark.min.css' => $this->t('Base16 / Equilibrium Gray Dark'),
        'base16/equilibrium-gray-light.min.css' => $this->t('Base16 / Equilibrium Gray Light'),
        'base16/equilibrium-light.min.css' => $this->t('Base16 / Equilibrium Light'),
        'base16/espresso.min.css' => $this->t('Base16 / Espresso'),
        'base16/eva-dim.min.css' => $this->t('Base16 / Eva'),
        'base16/eva.min.css' => $this->t('Base16 / Eva Dim'),
        'base16/flat.min.css' => $this->t('Base16 / Flat'),
        'base16/framer.min.css' => $this->t('Base16 / Framer'),
        'base16/fruit-soda.min.css' => $this->t('Base16 / Fruit Soda'),
        'base16/gigavolt.min.css' => $this->t('Base16 / Gigavolt'),
        'base16/github.min.css' => $this->t('Base16 / Github'),
        'base16/google-dark.min.css' => $this->t('Base16 / Google Dark'),
        'base16/google-light.min.css' => $this->t('Base16 / Google Light'),
        'base16/grayscale-dark.min.css' => $this->t('Base16 / Grayscale Dark'),
        'base16/grayscale-light.min.css' => $this->t('Base16 / Grayscale Light'),
        'base16/green-screen.min.css' => $this->t('Base16 / Green Screen'),
        'base16/gruvbox-dark-hard.min.css' => $this->t('Base16 / Gruvbox Dark Hard'),
        'base16/gruvbox-dark-medium.min.css' => $this->t('Base16 / Gruvbox Dark Medium'),
        'base16/gruvbox-dark-pale.min.css' => $this->t('Base16 / Gruvbox Dark Pale'),
        'base16/gruvbox-dark-soft.min.css' => $this->t('Base16 / Gruvbox Dark Soft'),
        'base16/gruvbox-light-hard.min.css' => $this->t('Base16 / Gruvbox Light Hard'),
        'base16/gruvbox-light-medium.min.css' => $this->t('Base16 / Gruvbox Light Medium'),
        'base16/gruvbox-light-soft.min.css' => $this->t('Base16 / Gruvbox Light Soft'),
        'base16/hardcore.min.css' => $this->t('Base16 / Hardcore'),
        'base16/harmonic16-dark.min.css' => $this->t('Base16 / Harmonic 16 Dark'),
        'base16/harmonic16-light.min.css' => $this->t('Base16 / Harmonic 16 Light'),
        'base16/heetch-dark.min.css' => $this->t('Base16 / Heetch Dark'),
        'base16/heetch-light.min.css' => $this->t('Base16 / Heetch Light'),
        'base16/helios.min.css' => $this->t('Base16 / Helios'),
        'base16/hopscotch.min.css' => $this->t('Base16 / Hopscotch'),
        'base16/horizon-dark.min.css' => $this->t('Base16 / Horizon Dark'),
        'base16/horizon-light.min.css' => $this->t('Base16 / Horizon Light'),
        'base16/humanoid-dark.min.css' => $this->t('Base16 / Humanoid Dark'),
        'base16/humanoid-light.min.css' => $this->t('Base16 / Humanoid Light'),
        'base16/ia-dark.min.css' => $this->t('Base16 / Ia Dark'),
        'base16/ia-light.min.css' => $this->t('Base16 / Ia Light'),
        'base16/icy-dark.min.css' => $this->t('Base16 / Icy Dark'),
        'base16/ir-black.min.css' => $this->t('Base16 / Ir Black'),
        'base16/isotope.min.css' => $this->t('Base16 / Isotope'),
        'base16/kimber.min.css' => $this->t('Base16 / Kimber'),
        'base16/london-tube.min.css' => $this->t('Base16 / London Tube'),
        'base16/macintosh.min.css' => $this->t('Base16 / Macintosh'),
        'base16/marrakesh.min.css' => $this->t('Base16 / Marrakesh'),
        'base16/materia.min.css' => $this->t('Base16 / Materia'),
        'base16/material-darker.min.css' => $this->t('Base16 / Material'),
        'base16/material-lighter.min.css' => $this->t('Base16 / Material Darker'),
        'base16/material-palenight.min.css' => $this->t('Base16 / Material Lighter'),
        'base16/material-vivid.min.css' => $this->t('Base16 / Material Palenight'),
        'base16/material.min.css' => $this->t('Base16 / Material Vivid'),
        'base16/mellow-purple.min.css' => $this->t('Base16 / Mellow Purple'),
        'base16/mexico-light.min.css' => $this->t('Base16 / Mexico Light'),
        'base16/mocha.min.css' => $this->t('Base16 / Mocha'),
        'base16/monokai.min.css' => $this->t('Base16 / Monokai'),
        'base16/nebula.min.css' => $this->t('Base16 / Nebula'),
        'base16/nord.min.css' => $this->t('Base16 / Nord'),
        'base16/nova.min.css' => $this->t('Base16 / Nova'),
        'base16/ocean.min.css' => $this->t('Base16 / Ocean'),
        'base16/oceanicnext.min.css' => $this->t('Base16 / Oceanicnext'),
        'base16/one-light.min.css' => $this->t('Base16 / One Light'),
        'base16/onedark.min.css' => $this->t('Base16 / Onedark'),
        'base16/outrun-dark.min.css' => $this->t('Base16 / Outrun Dark'),
        'base16/papercolor-dark.min.css' => $this->t('Base16 / Papercolor Dark'),
        'base16/papercolor-light.min.css' => $this->t('Base16 / Papercolor Light'),
        'base16/paraiso.min.css' => $this->t('Base16 / Paraiso'),
        'base16/pasque.min.css' => $this->t('Base16 / Pasque'),
        'base16/phd.min.css' => $this->t('Base16 / Phd'),
        'base16/pico.min.css' => $this->t('Base16 / Pico'),
        'base16/pop.min.css' => $this->t('Base16 / Pop'),
        'base16/porple.min.css' => $this->t('Base16 / Porple'),
        'base16/qualia.min.css' => $this->t('Base16 / Qualia'),
        'base16/railscasts.min.css' => $this->t('Base16 / Railscasts'),
        'base16/rebecca.min.css' => $this->t('Base16 / Rebecca'),
        'base16/ros-pine-dawn.min.css' => $this->t('Base16 / Ros Pine'),
        'base16/ros-pine-moon.min.css' => $this->t('Base16 / Ros Pine Dawn'),
        'base16/ros-pine.min.css' => $this->t('Base16 / Ros Pine Moon'),
        'base16/sagelight.min.css' => $this->t('Base16 / Sagelight'),
        'base16/sandcastle.min.css' => $this->t('Base16 / Sandcastle'),
        'base16/seti-ui.min.css' => $this->t('Base16 / Seti Ui'),
        'base16/shapeshifter.min.css' => $this->t('Base16 / Shapeshifter'),
        'base16/silk-dark.min.css' => $this->t('Base16 / Silk Dark'),
        'base16/silk-light.min.css' => $this->t('Base16 / Silk Light'),
        'base16/snazzy.min.css' => $this->t('Base16 / Snazzy'),
        'base16/solar-flare-light.min.css' => $this->t('Base16 / Solar Flare'),
        'base16/solar-flare.min.css' => $this->t('Base16 / Solar Flare Light'),
        'base16/solarized-dark.min.css' => $this->t('Base16 / Solarized Dark'),
        'base16/solarized-light.min.css' => $this->t('Base16 / Solarized Light'),
        'base16/spacemacs.min.css' => $this->t('Base16 / Spacemacs'),
        'base16/summercamp.min.css' => $this->t('Base16 / Summercamp'),
        'base16/summerfruit-dark.min.css' => $this->t('Base16 / Summerfruit Dark'),
        'base16/summerfruit-light.min.css' => $this->t('Base16 / Summerfruit Light'),
        'base16/synth-midnight-terminal-dark.min.css' => $this->t('Base16 / Synth Midnight Terminal Dark'),
        'base16/synth-midnight-terminal-light.min.css' => $this->t('Base16 / Synth Midnight Terminal Light'),
        'base16/tango.min.css' => $this->t('Base16 / Tango'),
        'base16/tender.min.css' => $this->t('Base16 / Tender'),
        'base16/tomorrow-night.min.css' => $this->t('Base16 / Tomorrow'),
        'base16/tomorrow.min.css' => $this->t('Base16 / Tomorrow Night'),
        'base16/twilight.min.css' => $this->t('Base16 / Twilight'),
        'base16/unikitty-dark.min.css' => $this->t('Base16 / Unikitty Dark'),
        'base16/unikitty-light.min.css' => $this->t('Base16 / Unikitty Light'),
        'base16/vulcan.min.css' => $this->t('Base16 / Vulcan'),
        'base16/windows-10-light.min.css' => $this->t('Base16 / Windows 10'),
        'base16/windows-10.min.css' => $this->t('Base16 / Windows 10 Light'),
        'base16/windows-95-light.min.css' => $this->t('Base16 / Windows 95'),
        'base16/windows-95.min.css' => $this->t('Base16 / Windows 95 Light'),
        'base16/windows-high-contrast-light.min.css' => $this->t('Base16 / Windows High Contrast'),
        'base16/windows-high-contrast.min.css' => $this->t('Base16 / Windows High Contrast Light'),
        'base16/windows-nt-light.min.css' => $this->t('Base16 / Windows Nt'),
        'base16/windows-nt.min.css' => $this->t('Base16 / Windows Nt Light'),
        'base16/woodland.min.css' => $this->t('Base16 / Woodland'),
        'base16/xcode-dusk.min.css' => $this->t('Base16 / Xcode Dusk'),
        'base16/zenburn.min.css' => $this->t('Base16 / Zenburn'),
        'brown-paper.min.css' => $this->t('Brown Paper'),
        'codepen-embed.min.css' => $this->t('Codepen Embed'),
        'color-brewer.min.css' => $this->t('Color Brewer'),
        'dark.min.css' => $this->t('Dark'),
        'devibeans.min.css' => $this->t('Devibeans'),
        'docco.min.css' => $this->t('Docco'),
        'far.min.css' => $this->t('Far'),
        'felipec.min.css' => $this->t('Felipec'),
        'foundation.min.css' => $this->t('Foundation'),
        'github-dark-dimmed.min.css' => $this->t('Github Dark Dimmed'),
        'github-dark.min.css' => $this->t('Github Dark'),
        'github.min.css' => $this->t('Github'),
        'gml.min.css' => $this->t('Gml'),
        'googlecode.min.css' => $this->t('Googlecode'),
        'gradient-dark.min.css' => $this->t('Gradient Dark'),
        'gradient-light.min.css' => $this->t('Gradient Light'),
        'grayscale.min.css' => $this->t('Grayscale'),
        'hybrid.min.css' => $this->t('Hybrid'),
        'idea.min.css' => $this->t('Idea'),
        'intellij-light.min.css' => $this->t('Intellij Light'),
        'ir-black.min.css' => $this->t('Ir Black'),
        'isbl-editor-dark.min.css' => $this->t('Isbl Editor Dark'),
        'isbl-editor-light.min.css' => $this->t('Isbl Editor Light'),
        'kimbie-dark.min.css' => $this->t('Kimbie Dark'),
        'kimbie-light.min.css' => $this->t('Kimbie Light'),
        'lightfair.min.css' => $this->t('Lightfair'),
        'lioshi.min.css' => $this->t('Lioshi'),
        'magula.min.css' => $this->t('Magula'),
        'mono-blue.min.css' => $this->t('Mono Blue'),
        'monokai-sublime.min.css' => $this->t('Monokai Sublime'),
        'monokai.min.css' => $this->t('Monokai'),
        'night-owl.min.css' => $this->t('Night Owl'),
        'nnfx-dark.min.css' => $this->t('Nnfx Dark'),
        'nnfx-light.min.css' => $this->t('Nnfx Light'),
        'nord.min.css' => $this->t('Nord'),
        'obsidian.min.css' => $this->t('Obsidian'),
        'panda-syntax-dark.min.css' => $this->t('Panda Syntax Dark'),
        'panda-syntax-light.min.css' => $this->t('Panda Syntax Light'),
        'paraiso-dark.min.css' => $this->t('Paraiso Dark'),
        'paraiso-light.min.css' => $this->t('Paraiso Light'),
        'pojoaque.min.css' => $this->t('Pojoaque'),
        'purebasic.min.css' => $this->t('Purebasic'),
        'qtcreator-dark.min.css' => $this->t('Qtcreator Dark'),
        'qtcreator-light.min.css' => $this->t('Qtcreator Light'),
        'rainbow.min.css' => $this->t('Rainbow'),
        'routeros.min.css' => $this->t('Routeros'),
        'school-book.min.css' => $this->t('School Book'),
        'shades-of-purple.min.css' => $this->t('Shades Of Purple'),
        'srcery.min.css' => $this->t('Srcery'),
        'stackoverflow-dark.min.css' => $this->t('Stackoverflow Dark'),
        'stackoverflow-light.min.css' => $this->t('Stackoverflow Light'),
        'sunburst.min.css' => $this->t('Sunburst'),
        'tokyo-night-dark.min.css' => $this->t('Tokyo Night Dark'),
        'tokyo-night-light.min.css' => $this->t('Tokyo Night Light'),
        'tomorrow-night-blue.min.css' => $this->t('Tomorrow Night Blue'),
        'tomorrow-night-bright.min.css' => $this->t('Tomorrow Night Bright'),
        'vs.min.css' => $this->t('Vs'),
        'vs2015.min.css' => $this->t('Vs 2015'),
        'xcode.min.css' => $this->t('Xcode'),
        'xt256.min.css' => $this->t('Xt 256'),
      ],
      '#default_value' => $default_values['raw_html_highlight_style'],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save my settings'),
    ];
    return $form;
  }

}
