/* ──────────────────────────────────────────────────────────────────────────
 * i18n PARTAGÉ du chrome de l'éditeur de page CMS (PageTabs.tsx + CmsPage.tsx).
 * Cette brique React NE partage PAS le dictionnaire i18n de l'hôte → dictionnaire
 * local, même pattern que DuplicatePageModal.tsx.
 * Usage : `const tr = peT()` en tête d'un composant, puis `tr.someKey`.
 * ────────────────────────────────────────────────────────────────────────── */

export type PeLang = 'fr' | 'en'

/** Langue courante du back-office (attribut <html lang>), défaut 'en' si pas 'fr'. */
export function peLang(): PeLang {
  return (document.documentElement.lang || 'en').toLowerCase().startsWith('fr') ? 'fr' : 'en'
}

const DICT: Record<PeLang, Record<string, string>> = {
  fr: {
    // ── commun ──
    loading: 'Chargement…',
    save: 'Enregistrer',
    cancel: 'Annuler',
    prev: '‹ Précédent',
    next: 'Suivant ›',
    pageWord: 'Page',

    // ── FlagSelect (PageTabs) ──
    choose: 'Choisissez',

    // ── PropertiesTab ──
    name: 'Nom',
    type: 'Type',
    template: 'Template',
    langNonEditable: 'Langue (non modifiable après création)',
    menuDisplay: 'Affichage menu',
    style: 'Style',
    taxonomy: 'Taxonomie',
    taxonomyPlaceholder: 'Séparez les mots-clefs avec une virgule',

    // ── SeoTab ──
    seoSectionMeta: 'Métadonnées',
    seoSectionMetaHint: 'Titre et description affichés dans les résultats de recherche et le partage social.',
    seoSectionUrls: 'URL & redirections',
    seoSectionUrlsHint: 'Réécriture d’URL, redirections et URL canonique.',
    metaTitle: 'Titre (meta title)',
    metaDesc: 'Description (meta description)',
    customUrl: 'URL personnalisée',
    customUrlPlaceholder: 'ex: nos-services',
    customUrlHint: 'URL réécrite (slug) de la page, ex. « nos-services ». Laisser vide pour l’URL générée automatiquement.',
    redirectUrl: 'URL de redirection',
    redirectUrlHint: 'Redirige les visiteurs quand la page est EN LIGNE. Page interne : saisir son URL ; page externe : URL complète avec http(s)://.',
    redirect301: 'Redirection 301',
    redirect301Hint: 'Redirection 301 (permanente) quand la page est HORS LIGNE. Page interne : son URL ; page externe : URL complète avec http(s)://.',
    canonical: 'Canonical',
    canonicalHint: 'URL de référence déclarée aux moteurs de recherche (balise rel="canonical"), pour éviter le contenu dupliqué.',

    // ── AnalyticsTab ──
    visits: 'Visites',
    sessions: 'Sessions',
    lastVisit: 'Dernière visite',
    recentVisits: 'Visites récentes',
    noVisit: 'Aucune visite enregistrée.',
    visitsWord: 'visites',

    // ── ScriptsTab ──
    headTop: 'Head — haut',
    headBottom: 'Head — bas',
    bodyBottom: 'Body — bas',
    headTopHint: 'Injecté en haut du <head> (méta, préconnexions…)',
    headBottomHint: "Injecté en bas du <head> (scripts d'analytics…)",
    bodyBottomHint: 'Injecté avant </body> (scripts de fin de page…)',
    saveScripts: 'Enregistrer les scripts',
    savingScripts: 'Enregistrement…',
    scriptsSaved: 'Scripts enregistrés.',
    emptyPlaceholder: '(vide)',

    // ── VersioningTab ──
    pageVersions: 'Versions de la page',
    colNumber: 'N°',
    colName: 'Nom',
    colModifiedOn: 'Modifiée le',
    colBy: 'Par',
    colActions: 'Actions',
    noVersion: 'Aucune version enregistrée.',
    view: '👁 Voir',
    restore: '↩ Restaurer',
    rename: '✎ Renommer',
    versionNamePlaceholder: 'Nom de la version',
    versionRenamed: 'Version renommée.',
    renameFailed: 'Le renommage a échoué.',
    versionRestored: "Version restaurée dans l'édition.",
    restoreFailed: 'La restauration a échoué.',
    previewUnavailable: 'Aperçu indisponible.',
    restoreVersionTitle: '↩ Restaurer la version',
    restoreConfirm1a: 'Restaurer la version',
    restoreConfirm1b: "dans l'édition en cours ?",
    restoreConfirm2a: 'brouillon actuel sera remplacé',
    restoreConfirm2b: 'par le contenu de cette version.',
    restoreConfirm2pre: 'Le',
    restoring: 'Restauration…',
    restoreBtn: 'Restaurer',
    versionsWord: 'versions',
    viewTip: 'Aperçu de cette version',
    restoreTip: "Restaurer cette version dans l'édition",
    renameTip: 'Nommer / renommer',

    // ── CommentsTab ──
    pageActivity: 'Activité de la page',
    addCommentPlaceholder: 'Ajouter un commentaire…',
    comment: 'Commenter',
    sending: 'Envoi…',
    noActivity: 'Aucune activité pour le moment.',
    commentBadge: '💬 Commentaire',
    wfValidation: 'Demande de validation',
    wfValidated: 'Validé',
    wfRefused: 'Refusé',
    itemsWord: 'éléments',

    // ── HistoricTab ──
    pageHistory: 'Historique de la page',
    colDate: 'Date',
    colAction: 'Action',
    colUser: 'Utilisateur',
    filter: 'Filtrer :',
    allActions: 'Toutes les actions',
    noHistory: 'Aucun historique.',
    noEntryForAction: 'Aucune entrée pour ce type d’action.',
    entriesWord: 'entrées',

    // ── LanguagesTab ──
    currentPage: 'page courante',
    openPageTitle: 'Ouvrir cette page',
    langVersions: 'Versions de langue de cette page',
    colLanguage: 'Langue',
    colLocale: 'Locale',
    colPageName: 'Nom de page',
    colId: 'ID',
    creatableLangs: 'Créer une version de langue',
    createLangHint: 'Crée une copie de cette page dans une autre langue (statut hors ligne).',
    createLangBtn: 'Créer',
    langCreating: 'Création…',
    langCreated: 'Version de langue créée.',
    langCreateFailed: 'Échec de la création de la version de langue.',
    noCreatableLangs: 'Cette page existe déjà dans toutes les langues configurées pour son site (ou aucune autre langue n’est disponible).',

    // ── CmsPage : en-tête ──
    modifiedOn: 'modifiée le',
    byWord: 'par',
    loadingEdition: "chargement de l'édition…",
    savingEdition: 'enregistrement…',
    statusOnline: 'En ligne',
    statusDraft: 'Brouillon',
    statusOffline: 'Hors ligne',
    statusLabel: 'STATUT',
    onlineTip: 'En ligne — cliquer pour dépublier',
    offlineTip: 'Hors ligne — cliquer pour publier',

    // ── CmsPage : bandeau verrou ──
    pageLocked: 'Page verrouillée',
    lockedBy: 'par',
    lockedSince: 'depuis le',
    lockedMsg: "Un autre utilisateur l’édite ; débloquez-la pour reprendre la main.",

    // ── CmsPage : modale Débloquer ──
    unlockTitle: 'Débloquer la page',
    unlockBody1: 'Cette page a été verrouillée',
    unlockBody1by: 'par',
    unlockBody1on: 'le',
    unlockBody2: 'Merci de confirmer le déblocage.',
    confirm: 'Confirmer',
    unlocking: 'Déblocage…',

    // ── CmsPage : modale Supprimer ──
    deleteTitle: 'Supprimer la page',
    deleteBody1a: 'Supprimer définitivement la page',
    deleteBody1b: '(brouillon',
    deleteBody1and: 'et',
    deleteBody1c: 'version publiée) ?',
    deleteBody2a: 'Les pages dans d’',
    deleteBody2b: 'autres langues',
    deleteBody2c: 'ne sont',
    deleteBody2d: 'pas',
    deleteBody2e: 'supprimées — ce sont des pages distinctes, simplement liées.',
    deleteBody3a: 'Cette action est',
    deleteBody3b: 'irréversible',
    deleteConfirm: 'Supprimer définitivement',
    deleting: 'Suppression…',

    // ── CmsPage : notify (titres) ──
    notifSave: 'Enregistrement',
    notifPublish: 'Publication',
    notifUnpublish: 'Dépublication',
    notifDraft: 'Brouillon',
    notifDelete: 'Suppression',
    notifDuplicate: 'Duplication',
    // ── CmsPage : notify (corps) ──
    pageSaved: 'La page a été enregistrée.',
    pagePublished: 'La page a été publiée.',
    pageUnpublished: 'La page a été dépubliée.',
    draftCleared: 'Le brouillon a été effacé.',
    pageDeleted: 'La page a été supprimée.',
    pageDuplicated: 'La page a été dupliquée.',
    saveFailed: 'L’enregistrement a échoué.',
    publishFailed: 'La publication a échoué.',
    unpublishFailed: 'La dépublication a échoué.',
    draftFailed: 'L’opération a échoué.',
    deleteFailedMsg: 'La suppression a échoué.',
    duplicateFailed: 'La duplication a échoué.',
    pageUnlocked: 'Page débloquée.',

    // ── CmsPage : divers ──
    clearDraftConfirm: 'Effacer le brouillon et revenir à la dernière version publiée ?',
    clearTitle: 'Effacer le brouillon',
    clearConfirmBtn: 'Effacer le brouillon',
    clearing: 'Effacement…',
    noDraftToClear: 'Cette page n’a pas d’édition en cours.',
    selectPage: "Sélectionnez une page dans l'arbre.",
    newPage: 'Nouvelle page',
    editionLoadingTip: 'Édition en cours de chargement…',
  },
  en: {
    // ── common ──
    loading: 'Loading…',
    save: 'Save',
    cancel: 'Cancel',
    prev: '‹ Previous',
    next: 'Next ›',
    pageWord: 'Page',

    // ── FlagSelect (PageTabs) ──
    choose: 'Choose',

    // ── PropertiesTab ──
    name: 'Name',
    type: 'Type',
    template: 'Template',
    langNonEditable: 'Language (cannot be changed after creation)',
    menuDisplay: 'Menu display',
    style: 'Style',
    taxonomy: 'Taxonomy',
    taxonomyPlaceholder: 'Separate keywords with a comma',

    // ── SeoTab ──
    seoSectionMeta: 'Metadata',
    seoSectionMetaHint: 'Title and description shown in search results and social sharing.',
    seoSectionUrls: 'URLs & redirections',
    seoSectionUrlsHint: 'URL rewriting, redirections and canonical URL.',
    metaTitle: 'Title (meta title)',
    metaDesc: 'Description (meta description)',
    customUrl: 'Custom URL',
    customUrlPlaceholder: 'e.g. our-services',
    customUrlHint: 'Rewritten URL (slug) of the page, e.g. “our-services”. Leave empty to use the auto-generated URL.',
    redirectUrl: 'Redirection URL',
    redirectUrlHint: 'Redirects visitors when the page is ONLINE. Internal page: enter its URL; external page: full URL with http(s)://.',
    redirect301: '301 redirect',
    redirect301Hint: '301 (permanent) redirect when the page is OFFLINE. Internal page: its URL; external page: full URL with http(s)://.',
    canonical: 'Canonical',
    canonicalHint: 'Preferred URL declared to search engines (rel="canonical" tag), to avoid duplicate content.',

    // ── AnalyticsTab ──
    visits: 'Visits',
    sessions: 'Sessions',
    lastVisit: 'Last visit',
    recentVisits: 'Recent visits',
    noVisit: 'No visit recorded.',
    visitsWord: 'visits',

    // ── ScriptsTab ──
    headTop: 'Head — top',
    headBottom: 'Head — bottom',
    bodyBottom: 'Body — bottom',
    headTopHint: 'Injected at the top of <head> (meta, preconnects…)',
    headBottomHint: 'Injected at the bottom of <head> (analytics scripts…)',
    bodyBottomHint: 'Injected before </body> (end-of-page scripts…)',
    saveScripts: 'Save scripts',
    savingScripts: 'Saving…',
    scriptsSaved: 'Scripts saved.',
    emptyPlaceholder: '(empty)',

    // ── VersioningTab ──
    pageVersions: 'Page versions',
    colNumber: 'No.',
    colName: 'Name',
    colModifiedOn: 'Modified on',
    colBy: 'By',
    colActions: 'Actions',
    noVersion: 'No version recorded.',
    view: '👁 View',
    restore: '↩ Restore',
    rename: '✎ Rename',
    versionNamePlaceholder: 'Version name',
    versionRenamed: 'Version renamed.',
    renameFailed: 'Rename failed.',
    versionRestored: 'Version restored into the edition.',
    restoreFailed: 'Restore failed.',
    previewUnavailable: 'Preview unavailable.',
    restoreVersionTitle: '↩ Restore version',
    restoreConfirm1a: 'Restore version',
    restoreConfirm1b: 'into the current edition?',
    restoreConfirm2a: 'current draft will be replaced',
    restoreConfirm2b: 'by the content of this version.',
    restoreConfirm2pre: 'The',
    restoring: 'Restoring…',
    restoreBtn: 'Restore',
    versionsWord: 'versions',
    viewTip: 'Preview this version',
    restoreTip: 'Restore this version into the edition',
    renameTip: 'Name / rename',

    // ── CommentsTab ──
    pageActivity: 'Page activity',
    addCommentPlaceholder: 'Add a comment…',
    comment: 'Comment',
    sending: 'Sending…',
    noActivity: 'No activity yet.',
    commentBadge: '💬 Comment',
    wfValidation: 'Validation request',
    wfValidated: 'Validated',
    wfRefused: 'Refused',
    itemsWord: 'items',

    // ── HistoricTab ──
    pageHistory: 'Page history',
    colDate: 'Date',
    colAction: 'Action',
    colUser: 'User',
    filter: 'Filter:',
    allActions: 'All actions',
    noHistory: 'No history.',
    noEntryForAction: 'No entry for this action type.',
    entriesWord: 'entries',

    // ── LanguagesTab ──
    currentPage: 'current page',
    openPageTitle: 'Open this page',
    langVersions: 'Language versions of this page',
    colLanguage: 'Language',
    colLocale: 'Locale',
    colPageName: 'Page name',
    colId: 'ID',
    creatableLangs: 'Create a language version',
    createLangHint: 'Creates a copy of this page in another language (offline status).',
    createLangBtn: 'Create',
    langCreating: 'Creating…',
    langCreated: 'Language version created.',
    langCreateFailed: 'Failed to create the language version.',
    noCreatableLangs: 'This page already exists in every language configured for its site (or no other language is available).',

    // ── CmsPage: header ──
    modifiedOn: 'modified on',
    byWord: 'by',
    loadingEdition: 'loading edition…',
    savingEdition: 'saving…',
    statusOnline: 'Online',
    statusDraft: 'Draft',
    statusOffline: 'Offline',
    statusLabel: 'STATUS',
    onlineTip: 'Online — click to unpublish',
    offlineTip: 'Offline — click to publish',

    // ── CmsPage: lock banner ──
    pageLocked: 'Page locked',
    lockedBy: 'by',
    lockedSince: 'since',
    lockedMsg: 'Another user is editing it; unlock it to take over.',

    // ── CmsPage: Unlock modal ──
    unlockTitle: 'Unlock page',
    unlockBody1: 'This page was locked',
    unlockBody1by: 'by',
    unlockBody1on: 'on',
    unlockBody2: 'Please confirm the unlock.',
    confirm: 'Confirm',
    unlocking: 'Unlocking…',

    // ── CmsPage: Delete modal ──
    deleteTitle: 'Delete page',
    deleteBody1a: 'Permanently delete the page',
    deleteBody1b: '(draft',
    deleteBody1and: 'and',
    deleteBody1c: 'published version)?',
    deleteBody2a: 'Pages in ',
    deleteBody2b: 'other languages',
    deleteBody2c: 'are',
    deleteBody2d: 'not',
    deleteBody2e: 'deleted — they are distinct pages, merely linked.',
    deleteBody3a: 'This action is',
    deleteBody3b: 'irreversible',
    deleteConfirm: 'Delete permanently',
    deleting: 'Deleting…',

    // ── CmsPage: notify (titles) ──
    notifSave: 'Save',
    notifPublish: 'Publication',
    notifUnpublish: 'Unpublication',
    notifDraft: 'Draft',
    notifDelete: 'Deletion',
    notifDuplicate: 'Duplication',
    // ── CmsPage: notify (bodies) ──
    pageSaved: 'The page has been saved.',
    pagePublished: 'The page has been published.',
    pageUnpublished: 'The page has been unpublished.',
    draftCleared: 'The draft has been cleared.',
    pageDeleted: 'The page has been deleted.',
    pageDuplicated: 'The page has been duplicated.',
    saveFailed: 'Save failed.',
    publishFailed: 'Publication failed.',
    unpublishFailed: 'Unpublication failed.',
    draftFailed: 'The operation failed.',
    deleteFailedMsg: 'Deletion failed.',
    duplicateFailed: 'Duplication failed.',
    pageUnlocked: 'Page unlocked.',

    // ── CmsPage: misc ──
    clearDraftConfirm: 'Clear the draft and revert to the last published version?',
    clearTitle: 'Erase draft',
    clearConfirmBtn: 'Erase draft',
    clearing: 'Erasing…',
    noDraftToClear: 'This page has no edition in progress.',
    selectPage: 'Select a page in the tree.',
    newPage: 'New page',
    editionLoadingTip: 'Edition loading…',
  },
}

/** Renvoie le dictionnaire de la langue courante. Usage : `const tr = peT()` puis `tr.key`. */
export function peT(): Record<string, string> {
  return DICT[peLang()]
}
