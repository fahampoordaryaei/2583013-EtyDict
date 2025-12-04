(() => {
    const container = document.getElementById('etymology-canvas');
    if (!container || typeof vis === 'undefined') {
        return;
    }

    const graphWrapper = container.closest('.etymology-graph-wrapper');
    const fullscreenBtn = document.getElementById('etymology-fullscreen-btn');
    const fullscreenIcon = fullscreenBtn?.querySelector('.etymology-fullscreen-icon');
    const ensureTrailingSlash = (value) => value.endsWith('/') ? value : `${value}/`;
    const baseUrl = ensureTrailingSlash(window.etydictBaseUrl ?? '/');
    const iconBase = baseUrl + 'assets/img/';
    const maximizeSrc = iconBase + 'maximize.svg';
    const minimizeSrc = iconBase + 'minimize.svg';
    const params = new URLSearchParams(window.location.search);
    const word = (params.get('w') || '').trim();
    const endpoint = baseUrl + 'api/ety.php?w=' + encodeURIComponent(word);

    container.innerHTML = '<div class="p-5 text-center m-auto fs-2">Loading etymology graph…</div>';

    loadGraph(endpoint);

    async function loadGraph(url) {
        try {
            const response = await fetch(url);
            if (!response.ok) {
                throw new Error('Failed to load etymology graph');
            }
            const payload = await response.json();
            const nodes = Array.isArray(payload.nodes) ? payload.nodes : [];
            const edges = Array.isArray(payload.edges) ? payload.edges : [];
            renderGraph(nodes, edges);
        } catch (error) {
            console.error(error);
        }
    }

    function requestFullscreen(element) {
        if (element.requestFullscreen) {
            element.requestFullscreen();
        } else if (element.webkitRequestFullscreen) {
            element.webkitRequestFullscreen();
        } else if (element.mozRequestFullScreen) {
            element.mozRequestFullScreen();
        } else if (element.msRequestFullscreen) {
            element.msRequestFullscreen();
        }
    }

    function exitFullscreen() {
        if (document.exitFullscreen) {
            document.exitFullscreen();
        } else if (document.webkitExitFullscreen) {
            document.webkitExitFullscreen();
        } else if (document.mozCancelFullScreen) {
            document.mozCancelFullScreen();
        } else if (document.msExitFullscreen) {
            document.msExitFullscreen();
        }
    }

    function updateFullscreenButton(isActive) {
        if (!fullscreenBtn) {
            return;
        }
        fullscreenBtn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        fullscreenBtn.setAttribute('title', isActive ? 'Exit fullscreen' : 'Enter fullscreen');
        if (fullscreenIcon) {
            fullscreenIcon.src = isActive ? minimizeSrc : maximizeSrc;
            fullscreenIcon.alt = isActive ? 'Exit fullscreen' : 'Enter fullscreen';
        }
    }

    function toggleFullscreenMode() {
        if (!graphWrapper) {
            return;
        }
        const isActive = document.fullscreenElement === graphWrapper;
        if (isActive) {
            exitFullscreen();
            updateFullscreenButton(false);
        } else {
            requestFullscreen(graphWrapper);
            updateFullscreenButton(true);
        }
    }

    if (fullscreenBtn) {
        fullscreenBtn.addEventListener('click', (event) => {
            event.preventDefault();
            toggleFullscreenMode();
        });
    }

    function buildTooltip(node) {
        return node.definition ? node.definition : '';
    }

    function makeColor(bg, highlightBg) {
        return {
            background: bg,
            border: bg,
            highlight: {
                background: highlightBg,
                border: highlightBg
            },
            hover: {
                background: bg,
                border: bg
            }
        };
    }

    const languageColors = {
        proto: makeColor('#5b2c83', '#7628b6ff'),
        ancient: makeColor('#8e2430', '#bc2e1eff'),
        classical: makeColor('#b3471b', '#da6c0cff'),
        antiquity: makeColor('#b8860b', '#c18d00ff'),
        medieval: makeColor('#2e7d4f', '#009641ff'),
        modern: makeColor('#22577a', '#1044a3ff')
    };

    const languageCategory = {
        'proto-indo-european': 'proto',
        'proto-germanic': 'proto',
        'proto-west-germanic': 'proto',
        'proto-italic': 'proto',
        'proto-hellenic': 'proto',

        'sumerian': 'ancient',
        'akkadian': 'ancient',

        'ancient greek': 'classical',
        'latin': 'classical',

        'late latin': 'antiquity',
        'old english': 'antiquity',
        'old norse': 'antiquity',
        'old french': 'antiquity',
        'middle persian': 'antiquity',

        'middle english': 'medieval',
        'anglo-norman': 'medieval',
        'medieval latin': 'medieval',

        'english': 'modern',
        'italian': 'modern',
        'french': 'modern',
        'hindi': 'modern',
        'persian': 'modern'
    };

    function renderGraph(nodes, edges) {
        container.innerHTML = '';

        const visNodes = new vis.DataSet(
            nodes.map((node) => {
                const lines = [];

                if (node.word) {
                    lines.push('<b>' + node.word + '</b>');
                }
                if (node.language) {
                    lines.push('');
                    lines.push('<i>' + node.language + '</i>');
                }

                const lang = (node.language || '').toLowerCase();
                const category = languageCategory[lang] || 'modern';
                const color = languageColors[category] || languageColors['modern'];

                return {
                    id: node.id,
                    label: lines.join('\n'),
                    title: buildTooltip(node),
                    color: color
                };
            })
        );

        const visEdges = new vis.DataSet(
            edges.map((edge) => ({
                from: edge.from,
                to: edge.to,
                arrows: 'from'
            }))
        );

        const options = {
            layout: {
                hierarchical: {
                    enabled: true,
                    direction: 'LR',
                    sortMethod: 'directed',
                    levelSeparation: 200,
                    nodeSpacing: 120,
                    treeSpacing: 200
                }
            },
            physics: {
                enabled: false,
            },
            interaction: {
                dragNodes: true,
                dragView: true,
                zoomView: true,
                tooltipDelay: 100
            },
            nodes: {
                shape: 'box',
                borderWidth: 0,
                margin: 10,
                shapeProperties: {
                    borderRadius: 8
                },
                widthConstraint: {
                    minimum: 100,
                    maximum: 200
                },
                heightConstraint: {
                    minimum: 40
                },
                color: {
                    background: '#22577a',
                    border: '#22577a',
                    highlight: {
                        background: '#113984ff',
                        border: '#113984ff'
                    },
                    hover: {
                        background: '#22577a',
                        border: '#22577a'
                    }
                },
                font: {
                    face: 'system-ui',
                    size: 18,
                    color: '#ffffff',
                    multi: 'html',
                    bold: {
                        color: '#ffffff',
                        size: 20
                    }
                }
            },
            edges: {
                arrows: {
                    from: { enabled: true }
                },
                smooth: {
                    enabled: true,
                    type: 'cubicBezier',
                    roundness: 0.4
                },
                color: {
                    color: '#287677ff',
                    highlight: '#13de86ff',
                    hover: '#19dd88ff'
                },
                width: 2
            }
        };

        new vis.Network(container, { nodes: visNodes, edges: visEdges }, options);

    }
})();
