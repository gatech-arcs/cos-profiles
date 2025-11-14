<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Extension\SandboxExtension;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Source;
use Twig\Template;
use Twig\TemplateWrapper;

/* themes/custom/gt_theme/templates/layout/page.html.twig */
class __TwigTemplate_d1664c8d85212a257598a6e7269bc68e extends Template
{
    private Source $source;
    /**
     * @var array<string, Template>
     */
    private array $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
            'alert' => [$this, 'block_alert'],
            'banner' => [$this, 'block_banner'],
            'help' => [$this, 'block_help'],
            'tabs' => [$this, 'block_tabs'],
            'breadcrumbs' => [$this, 'block_breadcrumbs'],
            'before_content' => [$this, 'block_before_content'],
            'full_width_before_content' => [$this, 'block_full_width_before_content'],
            'sidebar_first' => [$this, 'block_sidebar_first'],
            'highlighted' => [$this, 'block_highlighted'],
            'content' => [$this, 'block_content'],
            'sidebar_second' => [$this, 'block_sidebar_second'],
            'full_width_after_content' => [$this, 'block_full_width_after_content'],
            'after_content' => [$this, 'block_after_content'],
            'full_width_content' => [$this, 'block_full_width_content'],
            'full_width_content_two' => [$this, 'block_full_width_content_two'],
        ];
        $this->sandbox = $this->extensions[SandboxExtension::class];
        $this->checkSecurity();
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        // line 55
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "alert", [], "any", false, false, true, 55)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 56
            yield "    ";
            yield from $this->unwrap()->yieldBlock('alert', $context, $blocks);
        }
        // line 61
        yield from $this->load("@gt/parts/header.html.twig", 61)->unwrap()->yield($context);
        // line 63
        yield "<div role=\"main\" class=\"main-container gt-body-page js-quickedit-main-content \">
    ";
        // line 65
        yield "    ";
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "banner", [], "any", false, false, true, 65)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 66
            yield "        ";
            yield from $this->unwrap()->yieldBlock('banner', $context, $blocks);
            // line 71
            yield "    ";
        }
        // line 72
        yield "    ";
        // line 73
        yield "    <div class=\"container\">
        ";
        // line 75
        yield "        ";
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "help", [], "any", false, false, true, 75)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 76
            yield "            ";
            yield from $this->unwrap()->yieldBlock('help', $context, $blocks);
            // line 81
            yield "        ";
        }
        // line 82
        yield "        ";
        // line 83
        yield "        ";
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "tabs", [], "any", false, false, true, 83)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 84
            yield "            ";
            yield from $this->unwrap()->yieldBlock('tabs', $context, $blocks);
            // line 89
            yield "        ";
        }
        // line 90
        yield "        ";
        // line 91
        yield "        ";
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "breadcrumbs", [], "any", false, false, true, 91)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 92
            yield "            ";
            yield from $this->unwrap()->yieldBlock('breadcrumbs', $context, $blocks);
            // line 97
            yield "        ";
        }
        // line 98
        yield "        ";
        // line 99
        yield "        ";
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "before_content", [], "any", false, false, true, 99)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 100
            yield "            ";
            yield from $this->unwrap()->yieldBlock('before_content', $context, $blocks);
            // line 105
            yield "        ";
        }
        // line 106
        yield "    </div>
    ";
        // line 108
        yield "    ";
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "full_width_before_content", [], "any", false, false, true, 108)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 109
            yield "        ";
            yield from $this->unwrap()->yieldBlock('full_width_before_content', $context, $blocks);
            // line 114
            yield "    ";
        }
        // line 115
        yield "    <div class=\"gt-container container \">
        <div class=\"row\">
            ";
        // line 118
        yield "            ";
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "sidebar_first", [], "any", false, false, true, 118)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 119
            yield "                ";
            yield from $this->unwrap()->yieldBlock('sidebar_first', $context, $blocks);
            // line 124
            yield "            ";
        }
        // line 125
        yield "            ";
        // line 126
        yield "            ";
        $context["content_classes"] = [(((CoreExtension::getAttribute($this->env, $this->source,         // line 127
($context["page"] ?? null), "sidebar_first", [], "any", false, false, true, 127) && CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "sidebar_second", [], "any", false, false, true, 127))) ? ("col-sm-6") : ("")), (((CoreExtension::getAttribute($this->env, $this->source,         // line 128
($context["page"] ?? null), "sidebar_first", [], "any", false, false, true, 128) && Twig\Extension\CoreExtension::testEmpty(CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "sidebar_second", [], "any", false, false, true, 128)))) ? ("col-sm-9") : ("")), (((CoreExtension::getAttribute($this->env, $this->source,         // line 129
($context["page"] ?? null), "sidebar_second", [], "any", false, false, true, 129) && Twig\Extension\CoreExtension::testEmpty(CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "sidebar_first", [], "any", false, false, true, 129)))) ? ("col-sm-9") : ("")), (((Twig\Extension\CoreExtension::testEmpty(CoreExtension::getAttribute($this->env, $this->source,         // line 130
($context["page"] ?? null), "sidebar_first", [], "any", false, false, true, 130)) && Twig\Extension\CoreExtension::testEmpty(CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "sidebar_second", [], "any", false, false, true, 130)))) ? ("col-sm-12") : (""))];
        // line 132
        yield "            <section";
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["content_attributes"] ?? null), "addClass", [($context["content_classes"] ?? null)], "method", false, false, true, 132), "html", null, true);
        yield ">
                ";
        // line 134
        yield "                ";
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "highlighted", [], "any", false, false, true, 134)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 135
            yield "                    ";
            yield from $this->unwrap()->yieldBlock('highlighted', $context, $blocks);
            // line 138
            yield "                ";
        }
        // line 139
        yield "                ";
        // line 140
        yield "                ";
        yield from $this->unwrap()->yieldBlock('content', $context, $blocks);
        // line 146
        yield "            </section>
            ";
        // line 148
        yield "            ";
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "sidebar_second", [], "any", false, false, true, 148)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 149
            yield "                ";
            yield from $this->unwrap()->yieldBlock('sidebar_second', $context, $blocks);
            // line 154
            yield "            ";
        }
        // line 155
        yield "        </div>
    </div>
    ";
        // line 158
        yield "    ";
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "full_width_after_content", [], "any", false, false, true, 158)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 159
            yield "        ";
            yield from $this->unwrap()->yieldBlock('full_width_after_content', $context, $blocks);
            // line 164
            yield "    ";
        }
        // line 165
        yield "    ";
        // line 166
        yield "    ";
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "after_content", [], "any", false, false, true, 166)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 167
            yield "        ";
            yield from $this->unwrap()->yieldBlock('after_content', $context, $blocks);
            // line 174
            yield "    ";
        }
        // line 175
        yield "    ";
        // line 176
        yield "    ";
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "full_width_content", [], "any", false, false, true, 176)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 177
            yield "        ";
            yield from $this->unwrap()->yieldBlock('full_width_content', $context, $blocks);
            // line 182
            yield "    ";
        }
        // line 183
        yield "    ";
        // line 184
        yield "    ";
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "full_width_content_two", [], "any", false, false, true, 184)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 185
            yield "        ";
            yield from $this->unwrap()->yieldBlock('full_width_content_two', $context, $blocks);
            // line 190
            yield "    ";
        }
        // line 191
        yield "</div>
";
        // line 193
        yield "<footer id=\"footer\" class=\"gt-footer footer\">
    <div class=\"row\">
        ";
        // line 195
        if ((($tmp = ($context["super_footer"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 196
            yield "            <div class=\"col-12 gt-black-superfooter clearfix\">
                ";
            // line 198
            yield "                <div class=\"d-block d-md-none button-bar\">
                    <button class=\"btn-footer w-100\" type=\"button\" data-bs-toggle=\"collapse\" data-bs-target=\"#collapseFooter\" aria-expanded=\"false\" aria-controls=\"collapse\">
                        <p>";
            // line 200
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Resources"));
            yield "</p>
                    </button>
                </div>
                ";
            // line 203
            yield from $this->load("@gt/parts/super-footer.html.twig", 203)->unwrap()->yield($context);
            // line 204
            yield "            </div>
        ";
        }
        // line 206
        yield "        <div class=\"col-12 gt-gold-footer clearfix\">
            ";
        // line 208
        yield "            ";
        yield from $this->load("@gt/parts/footer.html.twig", 208)->unwrap()->yield($context);
        // line 209
        yield "        </div>
    </div> ";
        // line 211
        yield "</footer> ";
        $this->env->getExtension('\Drupal\Core\Template\TwigExtension')
            ->checkDeprecations($context, ["page", "content_attributes", "super_footer"]);        yield from [];
    }

    // line 56
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_alert(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        // line 57
        yield "        ";
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "alert", [], "any", false, false, true, 57), "html", null, true);
        yield "
    ";
        yield from [];
    }

    // line 66
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_banner(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        // line 67
        yield "            <div id=\"gt-banner\" class=\"jumbotron-fluid mb-4\" role=\"complementary\">
                ";
        // line 68
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "banner", [], "any", false, false, true, 68), "html", null, true);
        yield "
            </div>
        ";
        yield from [];
    }

    // line 76
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_help(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        // line 77
        yield "                <div id=\"gt-help\" role=\"complementary\">
                    ";
        // line 78
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "help", [], "any", false, false, true, 78), "html", null, true);
        yield "
                </div>
            ";
        yield from [];
    }

    // line 84
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_tabs(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        // line 85
        yield "                <div id=\"gt-tabs\" role=\"complementary\">
                    ";
        // line 86
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "tabs", [], "any", false, false, true, 86), "html", null, true);
        yield "
                </div>
            ";
        yield from [];
    }

    // line 92
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_breadcrumbs(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        // line 93
        yield "                <div id=\"gt-breadcrumbs-title\" class=\"breadcrumb-links\" role=\"complementary\">
                    ";
        // line 94
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "breadcrumbs", [], "any", false, false, true, 94), "html", null, true);
        yield "
                </div>
            ";
        yield from [];
    }

    // line 100
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_before_content(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        // line 101
        yield "                <div id=\"gt-before-content\" class=\"before-content\" role=\"complementary\">
                    ";
        // line 102
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "before_content", [], "any", false, false, true, 102), "html", null, true);
        yield "
                </div>
            ";
        yield from [];
    }

    // line 109
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_full_width_before_content(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        // line 110
        yield "            <div id=\"gt-banner\" class=\"jumbotron-fluid full-width-before-content\" role=\"complementary\">
                ";
        // line 111
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "full_width_before_content", [], "any", false, false, true, 111), "html", null, true);
        yield "
            </div>
        ";
        yield from [];
    }

    // line 119
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_sidebar_first(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        // line 120
        yield "                    <aside class=\"col-sm-3 sidebar-first\" role=\"complementary\">
                        ";
        // line 121
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "sidebar_first", [], "any", false, false, true, 121), "html", null, true);
        yield "
                    </aside>
                ";
        yield from [];
    }

    // line 135
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_highlighted(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        // line 136
        yield "                        <div class=\"highlighted\">";
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "highlighted", [], "any", false, false, true, 136), "html", null, true);
        yield "</div>
                    ";
        yield from [];
    }

    // line 140
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_content(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        // line 141
        yield "                    <a id=\"main-content\"></a>
                    <div class=\"gt-main-content\">
                        ";
        // line 143
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "content", [], "any", false, false, true, 143), "html", null, true);
        yield "
                    </div>
                ";
        yield from [];
    }

    // line 149
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_sidebar_second(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        // line 150
        yield "                    <aside class=\"col-sm-3\" class=\"sidebar-second\" role=\"complementary\">
                        ";
        // line 151
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "sidebar_second", [], "any", false, false, true, 151), "html", null, true);
        yield "
                    </aside>
                ";
        yield from [];
    }

    // line 159
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_full_width_after_content(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        // line 160
        yield "            <div id=\"gt-banner\" class=\"jumbotron-fluid full-width-after-content\" role=\"complementary\">
                ";
        // line 161
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "full_width_after_content", [], "any", false, false, true, 161), "html", null, true);
        yield "
            </div>
        ";
        yield from [];
    }

    // line 167
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_after_content(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        // line 168
        yield "            <div class=\"container\">
                <div id=\"gt-after-content\" class=\"after-content\" role=\"complementary\">
                    ";
        // line 170
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "after_content", [], "any", false, false, true, 170), "html", null, true);
        yield "
                </div>
            </div>
        ";
        yield from [];
    }

    // line 177
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_full_width_content(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        // line 178
        yield "            <div id=\"gt-banner\" class=\"jumbotron-fluid after-full-width-content-margin p-3\" role=\"complementary\">
                ";
        // line 179
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "full_width_content", [], "any", false, false, true, 179), "html", null, true);
        yield "
            </div>
        ";
        yield from [];
    }

    // line 185
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_full_width_content_two(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        // line 186
        yield "            <div id=\"gt-banner\" class=\"jumbotron-fluid after-full-width-content-no-margin\" role=\"complementary\">
                ";
        // line 187
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "full_width_content_two", [], "any", false, false, true, 187), "html", null, true);
        yield "
            </div>
        ";
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "themes/custom/gt_theme/templates/layout/page.html.twig";
    }

    /**
     * @codeCoverageIgnore
     */
    public function isTraitable(): bool
    {
        return false;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getDebugInfo(): array
    {
        return array (  539 => 187,  536 => 186,  529 => 185,  521 => 179,  518 => 178,  511 => 177,  502 => 170,  498 => 168,  491 => 167,  483 => 161,  480 => 160,  473 => 159,  465 => 151,  462 => 150,  455 => 149,  447 => 143,  443 => 141,  436 => 140,  428 => 136,  421 => 135,  413 => 121,  410 => 120,  403 => 119,  395 => 111,  392 => 110,  385 => 109,  377 => 102,  374 => 101,  367 => 100,  359 => 94,  356 => 93,  349 => 92,  341 => 86,  338 => 85,  331 => 84,  323 => 78,  320 => 77,  313 => 76,  305 => 68,  302 => 67,  295 => 66,  287 => 57,  280 => 56,  274 => 211,  271 => 209,  268 => 208,  265 => 206,  261 => 204,  259 => 203,  253 => 200,  249 => 198,  246 => 196,  244 => 195,  240 => 193,  237 => 191,  234 => 190,  231 => 185,  228 => 184,  226 => 183,  223 => 182,  220 => 177,  217 => 176,  215 => 175,  212 => 174,  209 => 167,  206 => 166,  204 => 165,  201 => 164,  198 => 159,  195 => 158,  191 => 155,  188 => 154,  185 => 149,  182 => 148,  179 => 146,  176 => 140,  174 => 139,  171 => 138,  168 => 135,  165 => 134,  160 => 132,  158 => 130,  157 => 129,  156 => 128,  155 => 127,  153 => 126,  151 => 125,  148 => 124,  145 => 119,  142 => 118,  138 => 115,  135 => 114,  132 => 109,  129 => 108,  126 => 106,  123 => 105,  120 => 100,  117 => 99,  115 => 98,  112 => 97,  109 => 92,  106 => 91,  104 => 90,  101 => 89,  98 => 84,  95 => 83,  93 => 82,  90 => 81,  87 => 76,  84 => 75,  81 => 73,  79 => 72,  76 => 71,  73 => 66,  70 => 65,  67 => 63,  65 => 61,  61 => 56,  59 => 55,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "themes/custom/gt_theme/templates/layout/page.html.twig", "/var/www/html/web/themes/custom/gt_theme/templates/layout/page.html.twig");
    }
    
    public function checkSecurity()
    {
        static $tags = ["if" => 55, "block" => 56, "include" => 61, "set" => 126];
        static $filters = ["escape" => 132, "t" => 200];
        static $functions = [];

        try {
            $this->sandbox->checkSecurity(
                ['if', 'block', 'include', 'set'],
                ['escape', 't'],
                [],
                $this->source
            );
        } catch (SecurityError $e) {
            $e->setSourceContext($this->source);

            if ($e instanceof SecurityNotAllowedTagError && isset($tags[$e->getTagName()])) {
                $e->setTemplateLine($tags[$e->getTagName()]);
            } elseif ($e instanceof SecurityNotAllowedFilterError && isset($filters[$e->getFilterName()])) {
                $e->setTemplateLine($filters[$e->getFilterName()]);
            } elseif ($e instanceof SecurityNotAllowedFunctionError && isset($functions[$e->getFunctionName()])) {
                $e->setTemplateLine($functions[$e->getFunctionName()]);
            }

            throw $e;
        }

    }
}
