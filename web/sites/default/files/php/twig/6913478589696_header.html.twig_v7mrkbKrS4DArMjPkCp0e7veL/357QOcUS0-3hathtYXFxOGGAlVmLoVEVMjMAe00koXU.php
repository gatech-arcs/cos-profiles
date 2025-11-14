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

/* @gt/parts/header.html.twig */
class __TwigTemplate_7ad8d2c888a74f251e60aba3def3af2f extends Template
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
            'header' => [$this, 'block_header'],
            'navigation' => [$this, 'block_navigation'],
            'utility_navigation' => [$this, 'block_utility_navigation'],
            'search' => [$this, 'block_search'],
        ];
        $this->sandbox = $this->extensions[SandboxExtension::class];
        $this->checkSecurity();
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        // line 2
        yield "<header id=\"gt-header\" role=\"banner\">
    ";
        // line 4
        yield "    ";
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "header", [], "any", false, false, true, 4)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 5
            yield "      ";
            yield from $this->unwrap()->yieldBlock('header', $context, $blocks);
            // line 8
            yield "    ";
        }
        // line 9
        yield "    ";
        // line 10
        yield "    <div class=\"container my-2\">
        <nav class=\"navbar navbar-expand-lg\">
            <div class=\"page-navigation main-nav collapse navbar-collapse\" id=\"navbarGTContent\">
                ";
        // line 14
        yield "                ";
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "navigation", [], "any", false, false, true, 14)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 15
            yield "                    ";
            yield from $this->unwrap()->yieldBlock('navigation', $context, $blocks);
            // line 19
            yield "                ";
        }
        // line 20
        yield "                ";
        // line 21
        yield "            <div id=\"utility-search-wrapper\" class=\"ml-auto d-sm-block d-md-flex justify-content-end flex-grow-1\">
                ";
        // line 22
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "utility_navigation", [], "any", false, false, true, 22)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 23
            yield "                    ";
            yield from $this->unwrap()->yieldBlock('utility_navigation', $context, $blocks);
            // line 28
            yield "                ";
        }
        // line 29
        yield "                ";
        // line 30
        yield "                ";
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "search", [], "any", false, false, true, 30)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 31
            yield "                    ";
            yield from $this->unwrap()->yieldBlock('search', $context, $blocks);
            // line 42
            yield "                ";
        }
        // line 43
        yield "               </div>
            </div>
        </nav>
    </div>
</header>
";
        $this->env->getExtension('\Drupal\Core\Template\TwigExtension')
            ->checkDeprecations($context, ["page"]);        yield from [];
    }

    // line 5
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_header(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        // line 6
        yield "        ";
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "header", [], "any", false, false, true, 6), "html", null, true);
        yield "
      ";
        yield from [];
    }

    // line 15
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_navigation(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        // line 16
        yield "                        <a id=\"main-navigation\"></a>
                        ";
        // line 17
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "navigation", [], "any", false, false, true, 17), "html", null, true);
        yield "
                    ";
        yield from [];
    }

    // line 23
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_utility_navigation(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        // line 24
        yield "                        <div class=\"utility-navigation float-start\">
                            ";
        // line 25
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "utility_navigation", [], "any", false, false, true, 25), "html", null, true);
        yield "
                        </div>
                    ";
        yield from [];
    }

    // line 31
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_search(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        // line 32
        yield "                        <!-- Trigger Buttons HTML -->
                        <a href=\"#search-container\" class=\"gt-search\" title=\"search\" data-bs-toggle=\"collapse\"
                           data-bs-target=\"#gt-search\"><i class=\"fas fa-search d-none d-md-block\"></i></a>
                        <!-- Collapsible Element HTML -->
                        <div id=\"search-container\">
                            <div id=\"gt-search\" class=\"collapse absolute\">
                                ";
        // line 38
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "search", [], "any", false, false, true, 38), "html", null, true);
        yield "
                            </div>
                        </div>
                    ";
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "@gt/parts/header.html.twig";
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
        return array (  176 => 38,  168 => 32,  161 => 31,  153 => 25,  150 => 24,  143 => 23,  136 => 17,  133 => 16,  126 => 15,  118 => 6,  111 => 5,  100 => 43,  97 => 42,  94 => 31,  91 => 30,  89 => 29,  86 => 28,  83 => 23,  81 => 22,  78 => 21,  76 => 20,  73 => 19,  70 => 15,  67 => 14,  62 => 10,  60 => 9,  57 => 8,  54 => 5,  51 => 4,  48 => 2,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "@gt/parts/header.html.twig", "/var/www/html/web/themes/custom/gt_theme/templates/parts/header.html.twig");
    }
    
    public function checkSecurity()
    {
        static $tags = ["if" => 4, "block" => 5];
        static $filters = ["escape" => 6];
        static $functions = [];

        try {
            $this->sandbox->checkSecurity(
                ['if', 'block'],
                ['escape'],
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
