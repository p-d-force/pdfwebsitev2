<?php declare(strict_types=1);
namespace App\Components;

/**
 * Chart component — reusable Chart.js canvas + script renderer.
 *
 * Usage:
 *   $chart = new Chart('myId', 'bar');
 *   $chart->setLabels(['2021', '2022']);
 *   $chart->addDataset('Widgets', [10, 20], ['palette' => 'cool']);
 *   echo $chart->render();
 */
class Chart
{
    private const ALLOWED_TYPES = ['bar', 'line', 'pie', 'doughnut', 'radar', 'polarArea', 'scatter', 'bubble'];

    public string $id;
    public string $type;
    public array $datasets = [];
    public array $labels = [];
    public array $options = [];
    public ?int $height = null;

    public static array $registry = [];

    public static array $palettes = [
        'default'   => ['#ff5a1f', '#ffa366', '#22c55e', '#f59e0b', '#ef4444', '#60a5fa', '#a78bfa', '#ec4899'],
        'sentiment' => ['#ef4444', '#f59e0b', '#22c55e'],
        'cool'      => ['#60a5fa', '#3b82f6', '#2563eb', '#1d4ed8', '#1e40af'],
        'warm'      => ['#ff5a1f', '#f97316', '#ea580c', '#c2410c', '#9a3412'],
    ];

    public function __construct(string $id, string $type)
    {
        if (!in_array($type, self::ALLOWED_TYPES, true)) {
            throw new \InvalidArgumentException("Invalid chart type '$type'. Allowed: " . implode(', ', self::ALLOWED_TYPES));
        }
        if (isset(self::$registry[$id])) {
            throw new \RuntimeException("Chart id '$id' already registered.");
        }
        $this->id   = $id;
        $this->type = $type;
    }

    public function addDataset(string $label, array $data, array $style = []): self
    {
        if (!isset($style['backgroundColor']) && isset($style['palette']) && isset(self::$palettes[$style['palette']])) {
            $palette = self::$palettes[$style['palette']];
            $idx     = count($this->datasets);
            $style['backgroundColor'] = $palette[$idx % count($palette)];
        }
        $this->datasets[] = array_merge(
            ['label' => $label, 'data' => $data],
            $style
        );
        return $this;
    }

    public function setLabels(array $labels): self
    {
        $this->labels = $labels;
        return $this;
    }

    public function setOption(string $key, $value): self
    {
        $keys = explode('.', $key);
        $target = &$this->options;
        $last = array_pop($keys);
        foreach ($keys as $k) {
            if (!isset($target[$k]) || !is_array($target[$k])) {
                $target[$k] = [];
            }
            $target = &$target[$k];
        }
        $target[$last] = $value;
        return $this;
    }

    public function setHeight(int $px): self
    {
        $this->height = $px;
        return $this;
    }

    /**
     * Render a full <canvas> + <script> for a Chart.js instance.
     */
    public function render(): string
    {
        self::$registry[$this->id] = true;

        $defaults = [
            'responsive'          => true,
            'maintainAspectRatio' => true,
            'animation'           => ['duration' => 800, 'easing' => 'easeOutQuart'],
            'plugins'             => [
                'legend'  => [
                    'labels'  => [
                        'color'   => '#a0a0a0',
                        'font'    => ['family' => 'Inter', 'size' => 11],
                        'padding' => 16,
                    ],
                ],
                'tooltip' => [
                    'backgroundColor' => '#1d1d1d',
                    'titleColor'      => '#e0e0e0',
                    'bodyColor'       => '#a0a0a0',
                    'borderColor'     => '#2a2a2a',
                    'borderWidth'     => 1,
                    'callbacks'       => [
                        'label' => '__CB_LABEL__',
                    ],
                ],
            ],
            'scales'              => [
                'x' => [
                    'grid'  => ['color' => 'rgba(42,42,42,0.5)'],
                    'ticks' => ['color' => '#767676', 'font' => ['family' => 'Inter', 'size' => 11]],
                ],
                'y' => [
                    'grid'  => ['color' => 'rgba(42,42,42,0.5)'],
                    'ticks' => ['color' => '#767676', 'font' => ['family' => 'Inter', 'size' => 11]],
                ],
            ],
        ];

        $merged = array_replace_recursive($defaults, $this->options);

        $canvasStyle = $this->height !== null ? " style=\"height:{$this->height}px\"" : '';

        $jsonOptions = str_replace(
            '"__CB_LABEL__"',
            self::tooltipLabelCallback(),
            json_encode($merged, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
        $jsonData = json_encode([
            'labels'   => $this->labels,
            'datasets' => $this->datasets,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        // Screen reader description
        $srDesc = ucfirst($this->type) . ' chart';
        if (!empty($this->labels)) $srDesc .= ' with ' . count($this->labels) . ' data points';
        if (!empty($this->datasets)) $srDesc .= ' and ' . count($this->datasets) . ' dataset' . (count($this->datasets) > 1 ? 's' : '');
        $html  = '<div class="sr-only" role="img" aria-label="' . h($srDesc) . '"></div>' . "\n";
        $html .= "<canvas id=\"{$this->id}\"{$canvasStyle} aria-hidden=\"true\" tabindex=\"0\" role=\"img\" aria-label=\"" . h($srDesc) . "\"></canvas>\n";
        $html .= "<script>\n";
        $html .= "(function(){\n";
        $html .= "try{\n";
        $html .= "var ctx=document.getElementById('{$this->id}');\n";
        $html .= "var c=new Chart(ctx,{\n";
        $html .= "  type: '{$this->type}',\n";
        $html .= "  data: {$jsonData},\n";
        $html .= "  options: {$jsonOptions}\n";
        $html .= "});\n";
        $html .= "var mq=window.matchMedia('(max-width:767px)');\n";
        $html .= "function respond(e){\n";
        $html .= "  var o=c.options;\n";
        $html .= "  if(e.matches){\n";
        $html .= "    if(o.plugins&&o.plugins.legend&&o.plugins.legend.labels) o.plugins.legend.labels.font={size:9};\n";
        $html .= "    if(o.scales&&o.scales.x&&o.scales.x.ticks) o.scales.x.ticks.font={size:9};\n";
        $html .= "    if(o.scales&&o.scales.y&&o.scales.y.ticks) o.scales.y.ticks.font={size:9};\n";
        $html .= "    if((c.config.type==='pie'||c.config.type==='doughnut')&&o.plugins&&o.plugins.legend) o.plugins.legend.display=false;\n";
        $html .= "    if(c.config.type==='bar'&&c.options.indexAxis!=='y') c.options.indexAxis='y';\n";
        $html .= "  }else{\n";
        $html .= "    if(o.plugins&&o.plugins.legend&&o.plugins.legend.labels) o.plugins.legend.labels.font={family:'Inter',size:11};\n";
        $html .= "    if(o.scales&&o.scales.x&&o.scales.x.ticks) o.scales.x.ticks.font={family:'Inter',size:11};\n";
        $html .= "    if(o.scales&&o.scales.y&&o.scales.y.ticks) o.scales.y.ticks.font={family:'Inter',size:11};\n";
        $html .= "    if((c.config.type==='pie'||c.config.type==='doughnut')&&o.plugins&&o.plugins.legend) o.plugins.legend.display=true;\n";
        $html .= "    if(c.config.type==='bar'&&c.options.indexAxis==='y'&&!c.__origIndexAxis) c.options.indexAxis='x';\n";
        $html .= "  }\n";
        $html .= "  c.update();\n";
        $html .= "}\n";
        $html .= "mq.addEventListener('change',respond);\n";
        $html .= "respond(mq);\n";
        $html .= "var mq2=window.matchMedia('(max-width:479px)');\n";
        $html .= "function respondSm(e){\n";
        $html .= "  if(e.matches){\n";
        $html .= "    if(c.options.scales&&c.options.scales.y&&c.options.scales.y.ticks) c.options.scales.y.ticks.display=false;\n";
        $html .= "    c.data.datasets.forEach(function(d){if(d.pointRadius!==undefined)d.pointRadius=2;if(d.pointHoverRadius!==undefined)d.pointHoverRadius=3;});\n";
        $html .= "  }else{\n";
        $html .= "    if(c.options.scales&&c.options.scales.y&&c.options.scales.y.ticks) c.options.scales.y.ticks.display=true;\n";
        $html .= "    c.data.datasets.forEach(function(d){if(d.pointRadius!==undefined)d.pointRadius=4;if(d.pointHoverRadius!==undefined)d.pointHoverRadius=6;});\n";
        $html .= "  }\n";
        $html .= "  c.update();\n";
        $html .= "}\n";
        $html .= "mq2.addEventListener('change',respondSm);\n";
        $html .= "respondSm(mq2);\n";
        $html .= "ctx.addEventListener('keydown',function(e){if(!c||!c.data||!c.data.labels)return;var idx=c.getActiveElements()[0]?c.getActiveElements()[0].index:-1;if(e.key==='ArrowRight')idx=(idx+1)%c.data.labels.length;else if(e.key==='ArrowLeft')idx=idx<=0?c.data.labels.length-1:idx-1;else return;e.preventDefault();c.setActiveElements([{datasetIndex:0,index:idx}]);c.tooltip.setActiveElements([{datasetIndex:0,index:idx}],{x:0,y:0});c.update();});\n";
        $html .= "}catch(e){console.warn('Chart {$this->id} failed:',e.message);var el=document.getElementById('{$this->id}');if(el)el.parentElement.innerHTML='<div style=\"color:#767676;text-align:center;padding:2rem\">Chart unavailable</div>';}\n";
        $html .= "})();\n";
        $html .= "</script>\n";
        $html .= "<button onclick=\"var c=Chart.getChart(document.getElementById('{$this->id}'));if(c){var a=document.createElement('a');a.href=c.toBase64Image();a.download='chart-{$this->id}.png';a.click();}\" class=\"btn btn-ghost btn-sm chart-dl-btn\" title=\"Download chart as PNG\" style=\"margin-top:0.5rem;font-size:0.75rem;\">&#8615; PNG</button>\n";

        return $html;
    }

    /**
     * Render an async-loading chart: canvas with shimmer skeleton + JS fetch wrapper.
     */
    public function renderAsync(string $apiUrl): string
    {
        self::$registry[$this->id] = true;

        $canvasStyle = $this->height !== null ? " style=\"height:{$this->height}px\"" : '';

        $defaults = [
            'responsive'          => true,
            'maintainAspectRatio' => true,
            'animation'           => ['duration' => 800, 'easing' => 'easeOutQuart'],
            'plugins'             => [
                'legend'  => [
                    'labels'  => [
                        'color'   => '#a0a0a0',
                        'font'    => ['family' => 'Inter', 'size' => 11],
                        'padding' => 16,
                    ],
                ],
                'tooltip' => [
                    'backgroundColor' => '#1d1d1d',
                    'titleColor'      => '#e0e0e0',
                    'bodyColor'       => '#a0a0a0',
                    'borderColor'     => '#2a2a2a',
                    'borderWidth'     => 1,
                    'callbacks'       => [
                        'label' => '__CB_LABEL__',
                    ],
                ],
            ],
            'scales'              => [
                'x' => [
                    'grid'  => ['color' => 'rgba(42,42,42,0.5)'],
                    'ticks' => ['color' => '#767676', 'font' => ['family' => 'Inter', 'size' => 11]],
                ],
                'y' => [
                    'grid'  => ['color' => 'rgba(42,42,42,0.5)'],
                    'ticks' => ['color' => '#767676', 'font' => ['family' => 'Inter', 'size' => 11]],
                ],
            ],
        ];

        $merged      = array_replace_recursive($defaults, $this->options);
        $jsonOptions = str_replace(
            '"__CB_LABEL__"',
            self::tooltipLabelCallback(),
            json_encode($merged, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
        $escUrl = addslashes($apiUrl);

        $html  = '';
        $html .= "<style>@keyframes shimmer{0%{background-position:200% 0}100%{background-position:-200% 0}}@keyframes shimmer-vert{0%{opacity:.3}50%{opacity:.7}100%{opacity:.3}}</style>\n";
        $html .= "<div class=\"chart-async-container\" style=\"position:relative;width:100%\" data-chart-id=\"{$this->id}\">\n";
        $html .= "  <div class=\"chart-shimmer\" data-shimmer style=\"position:absolute;inset:0;display:flex;flex-direction:column;gap:12px;padding:16px;pointer-events:none;z-index:1\">\n";
        $html .= "    <div style=\"height:16px;background:linear-gradient(90deg,#1a1a1a 25%,#2a2a2a 50%,#1a1a1a 75%);background-size:200% 100%;animation:shimmer 1.5s ease-in-out infinite;border-radius:4px;width:60%\"></div>\n";
        $html .= "    <div style=\"flex:1;display:flex;align-items:flex-end;gap:8px;padding-top:8px\">\n";
        for ($i = 0; $i < 6; $i++) {
            $h = rand(30, 90);
            $html .= "      <div style=\"flex:1;height:{$h}%;background:linear-gradient(180deg,#2a2a2a,#1a1a1a);border-radius:4px 4px 0 0;animation:shimmer 1.5s ease-in-out infinite\"></div>\n";
        }
        $html .= "    </div>\n";
        $html .= "    <div style=\"height:1px;background:#2a2a2a\"></div>\n";
        $html .= "    <div style=\"display:flex;gap:24px;justify-content:center\">\n";
        for ($i = 0; $i < 4; $i++) {
            $html .= "      <div style=\"height:10px;background:linear-gradient(90deg,#1a1a1a 25%,#2a2a2a 50%,#1a1a1a 75%);background-size:200% 100%;animation:shimmer 1.5s ease-in-out infinite;border-radius:4px;width:40px\"></div>\n";
        }
        $html .= "    </div>\n";
        $html .= "  </div>\n";
        $html .= "  <div class=\"chart-error\" data-error style=\"display:none;color:#ef4444;text-align:center;padding:40px 16px;font-family:Inter,sans-serif;font-size:14px\"></div>\n";
        $html .= "  <div class=\"chart-empty\" data-empty style=\"display:none;color:#a0a0a0;text-align:center;padding:40px 16px;font-family:Inter,sans-serif;font-size:14px\">No data for this selection</div>\n";
        $html .= "  <canvas id=\"{$this->id}\"{$canvasStyle} style=\"display:none\"></canvas>\n";
        $html .= "</div>\n";

        $html .= "<script>\n";
        $html .= "(function(){\n";
        $html .= "var c=document.querySelector('[data-chart-id=\"{$this->id}\"]');\n";
        $html .= "var sh=c.querySelector('[data-shimmer]');\n";
        $html .= "var er=c.querySelector('[data-error]');\n";
        $html .= "var em=c.querySelector('[data-empty]');\n";
        $html .= "var cv=document.getElementById('{$this->id}');\n";
        $html .= "fetch('{$escUrl}').then(function(r){return r.json()}).then(function(d){\n";
        $html .= "  if(d.error){er.textContent=d.error;er.style.display='block';sh.style.display='none'}\n";
        $html .= "  else if(Array.isArray(d)&&d.length===0){em.style.display='block';sh.style.display='none'}\n";
        $html .= "  else{\n";
        $html .= "    sh.style.display='none';cv.style.display='block';\n";
        $html .= "    var cd=d;\n";
        $html .= "    if(!d.labels||!d.datasets){\n";
        $html .= "      cd={labels:d.map(function(r){return r.school_year||r.label||''}),datasets:\n";
        $html .= "        Object.keys(d[0]||{}).filter(function(k){return k!=='school_year'&&k!=='label'}).map(function(k,i){\n";
        $html .= "          var cs=['#ff5a1f','#ffa366','#22c55e','#f59e0b','#ef4444','#60a5fa','#a78bfa','#ec4899'];\n";
        $html .= "          return {label:k,data:d.map(function(r){return r[k]}),backgroundColor:cs[i%cs.length]}\n";
        $html .= "        })}\n";
        $html .= "    }\n";
        $html .= "    new Chart(cv,{type:'{$this->type}',data:cd,options:{$jsonOptions}})\n";
        $html .= "  }\n";
        $html .= "}).catch(function(){er.textContent='Data unavailable';er.style.display='block';sh.style.display='none'});\n";
        $html .= "})();\n";
        $html .= "</script>";

        return $html;
    }

    /**
     * Generate the tooltip label callback JS — numbers with commas, units appended.
     */
    private static function tooltipLabelCallback(): string
    {
        return "function(context){var v=context.parsed.y!==undefined?context.parsed.y:context.parsed;var label=context.dataset.label||'';var unit=context.dataset.unit||'';return label+': '+(typeof v==='number'?v.toLocaleString()+unit:v+unit)}";
    }
}
