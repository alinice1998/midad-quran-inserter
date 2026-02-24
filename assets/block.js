/**
 * Midad Quran Inserter Block
 * No build process required context, using native wp variables.
 */

const { registerBlockType } = wp.blocks;
const { createElement: el, useState } = wp.element;
const { InspectorControls } = wp.blockEditor;
const { PanelBody, TextControl, Button, Spinner, Notice } = wp.components;
const apiFetch = wp.apiFetch;

// Safely map localized strings passed from PHP
const i18n = window.midadI18n || {
    title: 'Midad Quran Inserter',
    enter_search_term: 'Please enter a search term',
    no_verses_found: 'No verses found. Try another keyword.',
    api_error: 'API Error: ',
    check_api_key: 'Check your API Key settings.',
    search_quran: 'Search Quran (Kalimat API)',
    search_placeholder: 'Enter a word, surah, or surah:ayah...',
    searching: 'Searching...',
    search: 'Search',
    insert: 'Insert',
    surah: 'Surah',
    remove_search_again: 'Remove & Search Again'
};

// Register the block
registerBlockType('midad/quran-inserter', {
    title: i18n.title,
    icon: 'book-alt',
    category: 'widgets',
    attributes: {
        verseText: {
            type: 'string',
            default: '',
        },
        surahName: {
            type: 'string',
            default: '',
        },
        ayahNumber: {
            type: 'string',
            default: '',
        }
    },

    // Editor Interface
    edit: function (props) {
        const { attributes, setAttributes } = props;
        const [searchQuery, setSearchQuery] = useState('');
        const [results, setResults] = useState([]);
        const [isSearching, setIsSearching] = useState(false);
        const [error, setError] = useState(null);

        // Fetch Search Results via our custom REST API endpoint
        const performSearch = () => {
            if (!searchQuery.trim()) {
                setError(i18n.enter_search_term);
                return;
            }

            setIsSearching(true);
            setError(null);

            apiFetch({ path: `/midad/v1/search?query=${encodeURIComponent(searchQuery)}` })
                .then(res => {
                    if (res && res.length > 0) {
                        setResults(res);
                    } else {
                        setResults([]);
                        setError(i18n.no_verses_found);
                    }
                })
                .catch(err => {
                    setError(i18n.api_error + (err.message || i18n.check_api_key));
                })
                .finally(() => {
                    setIsSearching(false);
                });
        };

        // Render Search Input Interface
        const renderSearchUI = el('div', { className: 'midad-search-ui' },
            el('h4', null, i18n.search_quran),
            el('div', { className: 'midad-search-bar' },
                el(TextControl, {
                    value: searchQuery,
                    onChange: val => setSearchQuery(val),
                    placeholder: i18n.search_placeholder,
                    onKeyDown: e => {
                        if (e.key === 'Enter') performSearch();
                    }
                }),
                el(Button, {
                    isPrimary: true,
                    onClick: performSearch,
                    disabled: isSearching
                }, isSearching ? i18n.searching : i18n.search)
            ),
            isSearching && el(Spinner, { style: { marginTop: '10px' } }),
            error && el(Notice, { status: 'error', isDismissible: false }, error),

            results.length > 0 && el('div', { className: 'midad-results-list' },
                results.map((item, idx) => {
                    return el('div', {
                        key: idx,
                        className: 'midad-result-item'
                    },
                        el('p', { className: 'midad-result-text' }, item.text),
                        el('div', { className: 'midad-result-meta' },
                            el('span', null, item.reference),
                            el(Button, {
                                isSecondary: true,
                                onClick: () => {
                                    setAttributes({
                                        verseText: item.text,
                                        surahName: item.raw_surah,
                                        ayahNumber: item.raw_ayah
                                    });
                                    // clear list to show selection
                                    setResults([]);
                                }
                            }, i18n.insert)
                        )
                    );
                })
            )
        );

        // Render Display Interface (When verse is already selected)
        const renderVerseDisplay = el('div', { className: 'midad-inserted-verse editor-view' },
            el('div', { className: 'midad-arabic-text' },
                attributes.verseText,
                el('span', { className: 'midad-ayah-symbol' }, ` ﴿${attributes.ayahNumber}﴾`)
            ),
            el('div', { className: 'midad-verse-reference' }, `${i18n.surah} ${attributes.surahName}`),
            el(Button, {
                isDestructive: true,
                isSmall: true,
                style: { marginTop: '10px' },
                onClick: () => {
                    // Reset attributes to show search again
                    setAttributes({ verseText: '', surahName: '', ayahNumber: '' });
                }
            }, i18n.remove_search_again)
        );

        return el('div', { className: props.className },
            // If we have selected a verse, show it. Otherwise show search interface.
            attributes.verseText ? renderVerseDisplay : renderSearchUI
        );
    },

    // Frontend Render
    save: function (props) {
        const { attributes } = props;

        // If nothing was selected, render nothing
        if (!attributes.verseText) return null;

        return el('div', { className: 'midad-quran-block' },
            el('p', { className: 'midad-arabic-text' },
                attributes.verseText,
                el('span', { className: 'midad-ayah-symbol' }, ` ﴿${attributes.ayahNumber}﴾`)
            ),
            el('p', { className: 'midad-verse-reference' }, `${i18n.surah} ${attributes.surahName}`)
        );
    }
});
