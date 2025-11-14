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

/* themes/custom/gt_theme/templates/block/block--system-branding-block.html.twig */
class __TwigTemplate_180815b381b20dbd838dfd887456a1e0 extends Template
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
        // line 15
        yield "
";
        // line 17
        yield "<div class=\"container-fluid bg-gold-grad px-0\">
  <div class=\"container\">
    <div class=\"row\">
      <div class=\"col\" id=\"gt-logo\">
        <a href=\"https://gatech.edu/\" title=\"";
        // line 21
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Georgia Institute of Technology"));
        yield "\" rel=\"home\"
          class=\"site-branding-logo\">
          <img class=\"gt-logo\" src=\"/";
        // line 23
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["directory"] ?? null), "html", null, true);
        yield "/logo.svg\" width=\"244px\" height=\"42px\"
             alt=\"";
        // line 24
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Georgia Institute of Technology"));
        yield "\"/>
        </a>
      </div>
      <div id=\"mobile-button\" class=\"col d-lg-none\">
        <button class=\"navbar-toggler float-end\" type=\"button\" data-bs-toggle=\"collapse\"
            data-bs-target=\"#navbarGTContent\" aria-controls=\"navbarGTContent\" aria-expanded=\"false\"
            aria-label=\"Toggle navigation\">
          <span class=\"navbar-toggler-icon w-100\"></span>
          <span class=\"navbar-toggler-text w-100\">";
        // line 32
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("MENU"));
        yield "</span>
        </button>
      </div>
    </div>
  </div>
</div>

";
        // line 40
        yield "<div class=\"container\">
  <div class=\"row\">
    <div class=\"col\" id=\"site-name-slogan-wrapper\">
      ";
        // line 44
        yield "      ";
        if ((((($context["title_one_url"] ?? null) && ($context["site_name"] ?? null)) && ($context["title_two_url"] ?? null)) && ($context["site_slogan"] ?? null))) {
            // line 45
            yield "        <div class=\"site-title-multiple\">
          <h2 class=\"site-name\">
            <a href=\"";
            // line 47
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["title_one_url"] ?? null), "html", null, true);
            yield "\" title=\"";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["site_name"] ?? null), "html", null, true);
            yield "\" aria-label=\"";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["site_name"] ?? null), "html", null, true);
            yield "\">
              ";
            // line 48
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["site_name"] ?? null), "html", null, true);
            yield "
            </a>
          </h2>
          <h3 class=\"site-slogan\"><a href=\"";
            // line 51
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["title_two_url"] ?? null), "html", null, true);
            yield "\" title=\"";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["site_slogan"] ?? null), "html", null, true);
            yield "\"
                        aria-label=\"";
            // line 52
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["site_slogan"] ?? null), "html", null, true);
            yield "\">
              ";
            // line 53
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["site_slogan"] ?? null), "html", null, true);
            yield "</a>
          </h3>
        </div>
        ";
            // line 57
            yield "      ";
        } elseif (((($context["title_one_url"] ?? null) && ($context["site_name"] ?? null)) && ($context["site_slogan"] ?? null))) {
            // line 58
            yield "        <div class=\"site-title-multiple\">
          <h2 class=\"site-name\">
            <a href=\"";
            // line 60
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["title_one_url"] ?? null), "html", null, true);
            yield "\" title=\"";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["site_name"] ?? null), "html", null, true);
            yield "\" aria-label=\"";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["site_name"] ?? null), "html", null, true);
            yield "\">";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["site_name"] ?? null), "html", null, true);
            yield "</a>
          </h2>
          <h3 class=\"site-slogan\">";
            // line 62
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["site_slogan"] ?? null), "html", null, true);
            yield "</h3>
        </div>
        ";
            // line 65
            yield "      ";
        } elseif (((($context["title_two_url"] ?? null) && ($context["site_name"] ?? null)) && ($context["site_slogan"] ?? null))) {
            // line 66
            yield "        <div class=\"site-title-multiple\">
          <h2 class=\"site-name\">";
            // line 67
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["site_name"] ?? null), "html", null, true);
            yield "</h2>
          <h3 class=\"site-slogan\">
            <a href=\"";
            // line 69
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["title_two_url"] ?? null), "html", null, true);
            yield "\" title=\"";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["site_slogan"] ?? null), "html", null, true);
            yield "\" aria-label=\"";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["site_slogan"] ?? null), "html", null, true);
            yield "\">";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["site_slogan"] ?? null), "html", null, true);
            yield "</a>
          </h3>
        </div>
        ";
            // line 73
            yield "      ";
        } elseif ((($context["site_name"] ?? null) && ($context["site_slogan"] ?? null))) {
            // line 74
            yield "        <div class=\"site-title-multiple\">
          <h2 class=\"site-name\" title=\"";
            // line 75
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["site_name"] ?? null), "html", null, true);
            yield "\" aria-label=\"";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["site_name"] ?? null), "html", null, true);
            yield "\">";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["site_name"] ?? null), "html", null, true);
            yield "</h2>
          <h3 class=\"site-slogan\" title=\"";
            // line 76
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["site_slogan"] ?? null), "html", null, true);
            yield "\" aria-label=\"";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["site_slogan"] ?? null), "html", null, true);
            yield "\">";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["site_slogan"] ?? null), "html", null, true);
            yield "</h3>
        </div>
        ";
            // line 79
            yield "      ";
        } elseif ((( !Twig\Extension\CoreExtension::testEmpty(($context["site_name"] ?? null)) &&  !Twig\Extension\CoreExtension::testEmpty(($context["site_slogan"] ?? null))) &&  !Twig\Extension\CoreExtension::testEmpty(($context["title_two_url"] ?? null)))) {
            // line 80
            yield "        <div class=\"site-title-multiple\">
          <h2 class=\"site-name\" title=\"";
            // line 81
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["site_name"] ?? null), "html", null, true);
            yield "\" aria-label=\"";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["site_name"] ?? null), "html", null, true);
            yield "\">";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["site_name"] ?? null), "html", null, true);
            yield "</h2>
          <h3 class=\"site-slogan\">
            <a href=\"";
            // line 83
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["title_two_url"] ?? null), "html", null, true);
            yield "\" title=\"";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["site_slogan"] ?? null), "html", null, true);
            yield "\" aria-label=\"";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["site_slogan"] ?? null), "html", null, true);
            yield "\">";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["site_slogan"] ?? null), "html", null, true);
            yield "</a>
          </h3>
        </div>
        ";
            // line 87
            yield "      ";
        } elseif ((($tmp = ($context["title_one_url"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 88
            yield "        <div class=\"site-title-single\">
          <h2 class=\"site-name\">
            <a href=\"";
            // line 90
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["title_one_url"] ?? null), "html", null, true);
            yield "\" title=\"";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["site_name"] ?? null), "html", null, true);
            yield "\" aria-label=\"\">";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["site_name"] ?? null), "html", null, true);
            yield "</a>
          </h2>
        </div>
        ";
            // line 94
            yield "      ";
        } elseif (((( !Twig\Extension\CoreExtension::testEmpty(($context["site_name"] ?? null)) && Twig\Extension\CoreExtension::testEmpty(($context["title_one_url"] ?? null))) && Twig\Extension\CoreExtension::testEmpty(($context["site_slogan"] ?? null))) &&  !Twig\Extension\CoreExtension::testEmpty(($context["title_two_url"] ?? null)))) {
            // line 95
            yield "        <div class=\"site-title-single\">
          <h2 class=\"site-name\" title=\"";
            // line 96
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["site_name"] ?? null), "html", null, true);
            yield "\" aria-label=\"";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["site_name"] ?? null), "html", null, true);
            yield "\">";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["site_name"] ?? null), "html", null, true);
            yield "</h2>
        </div>
        ";
            // line 99
            yield "      ";
        } else {
            // line 100
            yield "        <div class=\"site-title-single\">
          <h2 class=\"site-name\" title=\"";
            // line 101
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["site_name"] ?? null), "html", null, true);
            yield "\" aria-label=\"";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["site_name"] ?? null), "html", null, true);
            yield "\">";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["site_name"] ?? null), "html", null, true);
            yield "</h2>
        </div>
      ";
        }
        // line 104
        yield "    </div>
  </div>
</div>
";
        $this->env->getExtension('\Drupal\Core\Template\TwigExtension')
            ->checkDeprecations($context, ["directory", "title_one_url", "site_name", "title_two_url", "site_slogan"]);        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "themes/custom/gt_theme/templates/block/block--system-branding-block.html.twig";
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
        return array (  269 => 104,  259 => 101,  256 => 100,  253 => 99,  244 => 96,  241 => 95,  238 => 94,  228 => 90,  224 => 88,  221 => 87,  209 => 83,  200 => 81,  197 => 80,  194 => 79,  185 => 76,  177 => 75,  174 => 74,  171 => 73,  159 => 69,  154 => 67,  151 => 66,  148 => 65,  143 => 62,  132 => 60,  128 => 58,  125 => 57,  119 => 53,  115 => 52,  109 => 51,  103 => 48,  95 => 47,  91 => 45,  88 => 44,  83 => 40,  73 => 32,  62 => 24,  58 => 23,  53 => 21,  47 => 17,  44 => 15,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "themes/custom/gt_theme/templates/block/block--system-branding-block.html.twig", "/var/www/html/web/themes/custom/gt_theme/templates/block/block--system-branding-block.html.twig");
    }
    
    public function checkSecurity()
    {
        static $tags = ["if" => 44];
        static $filters = ["t" => 21, "escape" => 23];
        static $functions = [];

        try {
            $this->sandbox->checkSecurity(
                ['if'],
                ['t', 'escape'],
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
