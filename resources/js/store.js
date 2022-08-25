Statamic.$store.registerModule(['publish', 'advancedSeo'], {

    namespaced: true,

    state: {
        conditions: {},
    },

    getters: {
        conditions: state => state.conditions,
    },

    actions: {
        fetchConditions({ commit }) {
            return Statamic.$request.post(`/!/advanced-seo/conditions`, window.location)
                .then(response => commit('setConditions', response.data))
                .catch(function (error) {
                    console.log(error);
                });
        },
    },

    mutations: {
        setConditions(state, conditions) {
            state.conditions = conditions;
        },
    }

})
