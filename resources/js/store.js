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
            const id = Statamic.$store.state.publish?.base?.values?.id;

            if (! id) {
                return
            }

            return Statamic.$request.get(`/!/advanced-seo/conditions/${id}`)
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
