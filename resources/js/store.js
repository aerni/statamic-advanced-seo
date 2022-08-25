Statamic.$store.registerModule(['publish', 'advancedSeo'], {

    namespaced: true,

    state: {
        conditions: {},
    },

    getters: {
        conditions: state => state.conditions,
    },

    actions: {
        fetchConditions({ commit }, payload) {
            return fetch(`https://statamic-advanced-seo.test/!/advanced-seo/conditions/${payload.id}`)
                .then(response => response.json())
                .then(conditions => commit('setConditions', conditions))
        },
    },

    mutations: {
        setConditions(state, conditions) {
            state.conditions = conditions;
        },
    }

})
