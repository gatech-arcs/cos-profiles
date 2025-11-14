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

/* @gt/parts/footer.html.twig */
class __TwigTemplate_267ec9382c37696f1487085da9213979 extends Template
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
        ];
        $this->sandbox = $this->extensions[SandboxExtension::class];
        $this->checkSecurity();
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        // line 3
        yield "<div class=\"container-fluid footer-top-bar d-none d-lg-block\">
</div>
";
        // line 6
        yield "<div id=\"gt-footer\" class=\"container-fluid footer-bottom-bar\">
    <div class=\"container pt-3\">
        <div class=\"row footer-content\">
            ";
        // line 10
        yield "            ";
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "footer_05", [], "any", false, false, true, 10)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 11
            yield "                <div id=\"address_text\" class=\"col-md-3 col-sm-12 my-2 order-md-1 order-2\">
                    ";
            // line 12
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "footer_05", [], "any", false, false, true, 12), "html", null, true);
            yield "
                </div>
            ";
        }
        // line 15
        yield "            ";
        // line 16
        yield "            ";
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "footer_06", [], "any", false, false, true, 16)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 17
            yield "                <div class=\"col-md-3 col-sm-12 my-2 order-md-2 order-3\">
                    ";
            // line 18
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "footer_06", [], "any", false, false, true, 18), "html", null, true);
            yield "
                </div>
            ";
        }
        // line 21
        yield "            ";
        // line 22
        yield "            ";
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "footer_07", [], "any", false, false, true, 22)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 23
            yield "                <div class=\"col-md-3 col-sm-12 my-2 order-md-3 order-4\">
                    ";
            // line 24
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "footer_07", [], "any", false, false, true, 24), "html", null, true);
            yield "
                </div>
            ";
        }
        // line 27
        yield "            ";
        // line 28
        yield "            ";
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "footer_08", [], "any", false, false, true, 28)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 29
            yield "                <div id=\"gt-logo-footer\" class=\"col-md-3 col-sm-12 my-2 order-md-4 order-1\">
                    ";
            // line 30
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["page"] ?? null), "footer_08", [], "any", false, false, true, 30), "html", null, true);
            yield "
                </div>
            ";
        }
        // line 33
        yield "        </div> ";
        // line 34
        yield "        <div class=\"row footer-bg-row\">
            <div class=\"col-12 col-sm-12 col-md-9 footer-bg-col\"></div>
        </div>
    </div> ";
        // line 38
        yield "</div> ";
        $this->env->getExtension('\Drupal\Core\Template\TwigExtension')
            ->checkDeprecations($context, ["page"]);        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "@gt/parts/footer.html.twig";
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
        return array (  114 => 38,  109 => 34,  107 => 33,  101 => 30,  98 => 29,  95 => 28,  93 => 27,  87 => 24,  84 => 23,  81 => 22,  79 => 21,  73 => 18,  70 => 17,  67 => 16,  65 => 15,  59 => 12,  56 => 11,  53 => 10,  48 => 6,  44 => 3,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "@gt/parts/footer.html.twig", "/var/www/html/web/themes/custom/gt_theme/templates/parts/footer.html.twig");
    }
    
    public function checkSecurity()
    {
        static $tags = ["if" => 10];
        static $filters = ["escape" => 12];
        static $functions = [];

        try {
            $this->sandbox->checkSecurity(
                ['if'],
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
