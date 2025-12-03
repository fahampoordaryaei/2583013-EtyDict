(() => {
    const container = document.getElementById('etymology-canvas');
    if (!container || typeof vis === 'undefined') {
        return;
    }

    const params = new URLSearchParams(window.location.search);
    const word = (params.get('w') || '').trim();

    const baseUrl = window.etydictBaseUrl ?? '/';
    const endpoint = baseUrl + 'api/ety.php?w=' + encodeURIComponent(word);

    container.innerHTML = '<div class="p-5 text-center m-auto fs-2">Loading etymology graph…</div>';

    fetch(endpoint)
        .then((response) => {
            if (!response.ok) {
                throw new Error('Failed to load etymology graph');
            }
            return response.json();
        })
        .then((payload) => {
            const nodes = Array.isArray(payload?.nodes) ? payload.nodes : [];
            const edges = Array.isArray(payload?.edges) ? payload.edges : [];
            if (!nodes.length) {
                return;
            }
            renderGraph(nodes, edges);

        })
        .catch((error) => {
            console.error(error);
        });

    function buildTooltip(node) {
        return node.definition ? node.definition : '';
    }
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

                return {
                    id: node.id,
                    label: lines.join('\n'),
                    title: buildTooltip(node)
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
                    levelSeparation: 180,
                    nodeSpacing: 120,
                    treeSpacing: 200
                }
            },
            physics: {
                enabled: false
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
                    minimum: 70,
                    maximum: 200
                },
                heightConstraint: {
                    minimum: 40
                },
                color: {
                    background: '#22577a',
                    border: '#22577a',
                    highlight: {
                        background: '#183c54ff',
                        border: '#183c54ff'
                    },
                    hover: {
                        background: '#22577a',
                        border: '#22577a'
                    }
                },
                font: {
                    face: 'system-ui',
                    size: 16,
                    color: '#ffffff',
                    multi: 'html',
                    bold: {
                        color: '#ffffff',
                        size: 16
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
                    color: '#38a3a5',
                    highlight: '#57cc99',
                    hover: '#57cc99'
                },
                width: 2
            }
        };

        new vis.Network(container, { nodes: visNodes, edges: visEdges }, options);

    }
})();
