import { Component } from 'react'
import type { ReactNode } from 'react'
import { hasPluginForm, PluginTabbedForm } from './PluginFormKit'
import type { PluginFormProps } from './PluginFormKit'
// Per-module native config tabs. Each plugin's config lives in ITS OWN module as one or more TABS,
// registered into the shared tab registry (PluginFormKit). A module's registration fn is imported here
// (relative path into the single melis-cms SPA build) and CALLED — explicit, so nothing is tree-shaken.
// Other modules can register additional tabs for the SAME plugin the same way (generic + modular).
import { registerMelisFrontPlugins } from '../../../melis-front/ui-react/plugin-config/MelisFrontPlugins'
import { registerMelisCmsNewsPlugins } from '../../../melis-cms-news/ui-react/plugin-config/MelisCmsNewsPlugins'
import { registerMelisCmsSliderPlugins } from '../../../melis-cms-slider/ui-react/plugin-config/MelisCmsSliderPlugins'
import { registerMelisCmsProspectsPlugins } from '../../../melis-cms-prospects/ui-react/plugin-config/MelisCmsProspectsPlugins'
import { registerMelisCmsBlogPlugins } from '../../../melis-cms-blog/ui-react/plugin-config/MelisCmsBlogPlugins'
import { registerMelisCmsUserAccountPlugins } from '../../../melis-cms-user-account/ui-react/plugin-config/MelisCmsUserAccountPlugins'
import { registerMelisCmsCommentsPlugins } from '../../../melis-cms-comments/ui-react/plugin-config/MelisCmsCommentsPlugins'
// melis-cms-category2 registers its own plugin AND contributes a category filter tab to the news plugins.
import { registerMelisCmsCategory2Plugins } from '../../../melis-cms-category2/ui-react/plugin-config/MelisCmsCategory2Plugins'
// melis-cache-internal contributes a GLOBAL tab ("Cache partiel") shown on every plugin — modular proof.
import { registerMelisCacheInternalPlugins } from '../../../melis-cache-internal/ui-react/plugin-config/MelisCacheInternalPartialCaching'

registerMelisFrontPlugins()
registerMelisCmsNewsPlugins()
registerMelisCmsSliderPlugins()
registerMelisCmsProspectsPlugins()
registerMelisCmsBlogPlugins()
registerMelisCmsUserAccountPlugins()
registerMelisCmsCommentsPlugins()
registerMelisCmsCategory2Plugins()
registerMelisCacheInternalPlugins()

export { hasPluginForm, PluginTabbedForm }
export type { PluginFormProps }

/**
 * PluginForms — the FULL-REACT side of plugin configuration (evo/page-edition-react, Phase 3).
 *
 * A plugin with ≥1 registered React tab (hasPluginForm) is configured by the GENERIC tabbed form
 * (PluginTabbedForm), which collects its tabs (own + other-module contributions) and submits every
 * field to the SAME stateless endpoint the generic legacy iframe uses — byte-compatible XML. A plugin
 * with no registered tab falls back to that legacy iframe. A form that throws is caught by
 * <PluginFormBoundary> and the caller falls back to the iframe (config is never blocked by a bug).
 */

export class PluginFormBoundary extends Component<{ onError: () => void; children: ReactNode }, { failed: boolean }> {
  constructor(props: { onError: () => void; children: ReactNode }) {
    super(props)
    this.state = { failed: false }
  }
  static getDerivedStateFromError() { return { failed: true } }
  componentDidCatch() { this.props.onError() }
  render() { return this.state.failed ? null : this.props.children }
}
