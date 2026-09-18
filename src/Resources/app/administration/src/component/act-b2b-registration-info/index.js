import template from './act-b2b-registration-info.html.twig';
import './act-b2b-registration-info.scss';

const { Component } = Shopware;

const HOW_TO_ITEM_COUNT = 5;

Component.register('act-b2b-registration-info', {
    template,

    computed: {
        howToItems() {
            return Array.from(
                { length: HOW_TO_ITEM_COUNT },
                (_, index) => `act-b2b-registration.settings.info.howTo.item${index + 1}`,
            );
        },
    },
});
