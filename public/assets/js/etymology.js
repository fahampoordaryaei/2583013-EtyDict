const api_url = 'https://api.etymologyexplorer.com/prod';
const api_autocomplete_url = api_url + '/autocomplete?word=';

async function api_json(url) {
    const response = await fetch(url, {
        headers: {
            Accept: 'application/json'
        }
    });
    if (!response.ok) {
        throw new Error('JS API error ' + response.status);
    }
    return await response.json();
}

function api_autocomplete(word, language = 'English') {
    const url = api_autocomplete_url + encodeURIComponent(word) + '&language=' + encodeURIComponent(language);
    return api_json(url);
}

async function api_get_word_id(word, language = 'English') {
    const data = await api_autocomplete(word, language);
    const list = data.auto_complete_data; // '|| [];'
    if (list.length === 0) {
        return null;
    }
    return list[0]._id;
}

function api_get_trees(id) {
    if (!id) {
        return null;
    }
    const url = api_url + '/get_tree?id=' + encodeURIComponent(id);
    return api_json(url);
}
