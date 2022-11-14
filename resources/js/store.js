Statamic.$store.registerModule(['publish', 'advancedSeo'], {

    namespaced: true,

    state: {
        conditions: null,
    },

    getters: {
        conditions: state => state.conditions,
    },

    actions: {
        fetchConditions({ commit }) {
            return Statamic.$request.post(`/!/advanced-seo/conditions`, {
                url: window.location,
                id: Statamic.$store.state.publish?.base?.values?.id,
                site: Statamic.$store.state.publish.base.site,
            })
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
