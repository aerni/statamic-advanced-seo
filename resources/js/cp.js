import DefaultsPublishForm from './components/DefaultsPublishForm'

Statamic.booting(() => {
    Statamic.component('defaults-publish-form', DefaultsPublishForm)
})
