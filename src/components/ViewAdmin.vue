<!--
  - Copyright (c) 2023. The translate contributors.
  -
  - This file is licensed under the Affero General Public License version 3 or later. See the COPYING file.
  -->

<template>
	<div id="translate">
		<figure v-if="loading" class="icon-loading loading" />
		<figure v-if="!loading && success" class="icon-checkmark success" />
		<NcSettingsSection :title="t('translate', 'Status')">
			<NcNoteCard v-if="modelsDownloaded" show-alert type="success">
				{{ t('translate', 'Machine learning models have been downloaded successfully.') }}
			</NcNoteCard>
			<NcNoteCard v-else-if="modelsDownloaded === false" type="error">
				{{ t('translate', 'The machine learning models still need to be downloaded (see below).') }}
			</NcNoteCard>
			<NcNoteCard v-if="nodejs === false" type="error">
				{{ t('translate', 'Could not execute the node.js executable. You may need to set the path to a working executable manually. (See below.)') }}
			</NcNoteCard>
			<NcNoteCard v-if="avx === false" type="error">
				{{ t('translate', 'It seems that your server processor does not support AVX instructions. Without AVX instructions this app currently does not work.') }}
			</NcNoteCard>
		</NcSettingsSection>
		<NcSettingsSection :title="t('translate', 'Resources')">
			<p>{{ t('translate', 'By default all available CPU cores will be used which may put your system under considerable load. To avoid this, you can limit the amount of CPU Cores used.') }}</p>
			<p>
				<NcTextField
					:value.sync="settings['threads']"
					:label-visible="true"
					:label="t('translate', 'The number of threads to use (0 for no limit)')"
					@update:value="onChange" />
			</p>
		</NcSettingsSection>
    <NcSettingsSection :title="t('recognize', 'Node.js')">
      <p v-if="nodejs === undefined">
        <span class="icon-loading-small" />&nbsp;&nbsp;&nbsp;&nbsp;{{ t('recognize', 'Checking Node.js') }}
      </p>
      <NcNoteCard v-else-if="nodejs === false">
        {{ t('recognize', 'Could not execute the Node.js binary. You may need to set the path to a working binary manually. ') }}
      </NcNoteCard>
      <NcNoteCard v-else type="success">
        {{ t('recognize', 'Node.js {version} binary was installed successfully.', { version: nodejs }) }}
      </NcNoteCard>
      <p>
        {{ t('recognize', 'If the shipped Node.js binary doesn\'t work on your system for some reason you can set the path to a custom node.js binary. Currently supported is Node v18.0.0 and newer v18 releases.') }}
      </p>
      <p>
        <input v-model="settings['node_binary']" type="text" @change="onChange">
      </p>
      <p>{{ t('recognize', 'For Nextcloud Snap users, you need to adjust this path to point to the snap\'s "current" directory as the pre-configured path will change with each update. For example, set it to "/var/snap/nextcloud/current/nextcloud/extra-apps/recognize/bin/node" instead of "/var/snap/nextcloud/9337974/nextcloud/extra-apps/recognize/bin/node"') }}</p>
    </NcSettingsSection>
	</div>
</template>

<script>
import { NcNoteCard, NcSettingsSection, NcCheckboxRadioSwitch, NcTextField } from '@nextcloud/vue'
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { loadState } from '@nextcloud/initial-state'

const SETTINGS = [
		'node_binary',
		'threads',
]

export default {
	name: 'ViewAdmin',
	components: { NcSettingsSection, NcNoteCard, NcCheckboxRadioSwitch, NcTextField },

	data() {
		return {
			loading: false,
			success: false,
			error: '',
			settings: SETTINGS.reduce((obj, key) => ({ ...obj, [key]: '' }), {}),
			timeout: null,
			avx: undefined,
			nodejs: undefined,
			threads: undefined,
			modelsDownloaded: null,
		}
	},

	watch: {
		error(error) {
			if (!error) return
			OC.Notification.showTemporary(error)
		},
	},
	async created() {
		this.modelsDownloaded = loadState('translate', 'modelsDownloaded')
		this.getAVX()
		this.getNodejsStatus()

		try {
			const settings = loadState('translate', 'settings')
			for (const setting of SETTINGS) {
				this.settings[setting] = settings[setting]
			}
		} catch (e) {
			this.error = this.t('translate', 'Failed to load settings')
			throw e
		}
	},

	methods: {
		async getAVX() {
			const resp = await axios.get(generateUrl('/apps/translate/admin/avx'))
			const { avx } = resp.data
			this.avx = avx
		},
	  async getNodejsStatus() {
			const resp = await axios.get(generateUrl('/apps/translate/admin/nodejs'))
			const { nodejs } = resp.data
			this.nodejs = nodejs
		},
		onChange() {
			if (this.timeout) {
				clearTimeout(this.timeout)
			}
			setTimeout(() => {
				this.submit()
			}, 1000)
		},

		async submit() {
			this.loading = true
			for (const setting in this.settings) {
				await this.setValue(setting, this.settings[setting])
			}
			this.loading = false
			this.success = true
			setTimeout(() => {
				this.success = false
			}, 3000)
		},

		async setValue(setting, value) {
			try {
				await axios.put(generateUrl(`/apps/translate/admin/settings/${setting}`), {
					value,
				})
			} catch (e) {
				this.error = this.t('translate', 'Failed to save settings')
				throw e
			}
		},

		async getValue(setting) {
			try {
				const res = await axios.get(generateUrl(`/apps/translate/admin/settings/${setting}`))
				if (res.status !== 200) {
					this.error = this.t('translate', 'Failed to load settings')
					console.error('Failed request', res)
					return
				}
				return res.data.value
			} catch (e) {
				this.error = this.t('translate', 'Failed to load settings')
				throw e
			}
		},
	},
}
</script>
<style>
figure[class^='icon-'] {
	display: inline-block;
}

#translate {
	position: relative;
}

#translate .loading,
#translate .success {
	position: fixed;
	top: 70px;
	right: 20px;
}

#translate label {
	margin-top: 10px;
	display: flex;
}

#translate label > * {
	padding: 8px 0;
	padding-left: 6px;
}

#translate input[type=text], #translate input[type=password] {
	width: 50%;
	min-width: 300px;
	display: block;
}

#translate a:link, #translate a:visited, #translate a:hover {
	text-decoration: underline;
}
</style>
