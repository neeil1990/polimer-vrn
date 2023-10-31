import "./ui.vue.components.smiles.css";

/**
 * Bitrix UI
 * Smiles Vue component
 *
 * @package bitrix
 * @subpackage ui
 * @copyright 2001-2019 Bitrix
 */

BX.Vue.component('bx-smiles',
{
	/**
	 * @emits 'selectSmile' {text: string}
	 * @emits 'selectSet' {setId: number}
	 */
	data()
	{
		return {
			smiles: [],
			sets: []
		}
	},
	created()
	{
		this.setSelected = 0;
		this.serverLoad = false;

		this.smilesController = new SmileData(this.$root.$bitrixRestClient);
		this.smilesController.loadFromCache().then((result) => {
			if (this.serverLoad)
				return true;

			this.smiles = result.smiles;
			this.sets = result.sets.map((element, index) => {
				element.selected = this.setSelected === index;
				return element;
			});
		});

		this.smilesController.loadFromServer().then((result) => {
			this.smiles = result.smiles;
			this.sets = result.sets.map((element, index) => {
				element.selected = this.setSelected === index;
				return element;
			});
		})
	},
	methods:
	{
		selectSet(setId)
		{
			this.$emit('selectSet', {setId});

			this.smilesController.changeSet(setId).then((result) => {
				this.smiles = result;
				this.sets.map(set => {
					set.selected = set.id === setId;
					if (set.selected)
					{
						this.setSelected = setId;
					}
					return set;
				});
				this.$refs.elements.scrollTop = 0;
			});
		},
		selectSmile(text)
		{
			this.$emit('selectSmile', {text: ' '+text+' '});
		}
	},
	template: `
		<div class="bx-ui-smiles-box">
			<div class="bx-ui-smiles-elements-wrap" ref="elements">
				<template v-if="!smiles.length">
					<svg class="bx-ui-smiles-loading-circular" viewBox="25 25 50 50">
						<circle class="bx-ui-smiles-loading-path" cx="50" cy="50" r="20" fill="none" stroke-miterlimit="10"/>
						<circle class="bx-ui-smiles-loading-inner-path" cx="50" cy="50" r="20" fill="none" stroke-miterlimit="10"/>
					</svg>
				</template>
				<template v-else v-for="smile in smiles">
					<div class="bx-ui-smiles-smile">
						<img v-bx-lazyload :key="smile.id"
							class="bx-ui-smiles-smile-icon"
							:data-lazyload-src="smile.image"
							data-lazyload-error-class="bx-ui-smiles-smile-icon-error"
							:title="smile.name"
							:style="{height: (smile.originalHeight*0.5)+'px', width: (smile.originalWidth*0.5)+'px'}"
							@click="selectSmile(smile.typing)"
						/>
					</div>
				</template>
			</div>
			<template v-if="sets.length > 1">
				<div class="bx-ui-smiles-sets">
					<template v-for="set in sets">
						<div :class="['bx-ui-smiles-set', {'bx-ui-smiles-set-selected': set.selected}]">
							<img v-bx-lazyload :key="set.id"
								class="bx-ui-smiles-set-icon"
								:data-lazyload-src="set.image"
								data-lazyload-error-class="bx-ui-smiles-set-icon-error"
								:title="set.name"
								@click="selectSet(set.id)"
							/>
						</div>
					</template>
				</div>
			</template>
		</div>
	`
});

class SmileData
{
	constructor(restClient)
	{
		if (typeof restClient !== 'undefined')
		{
			this.restClient = restClient;
		}
		else
		{
			this.restClient = new BX.RestClient();
		}

		this.db = new BX.IndexedDB('bx-ui-smiles');
		this.db.version(1).stores({
			sets: "id, parentId, name, type, image",
			smiles: "id, setId, name, image, typing, width, height, originalWidth, originalHeight, definition",
		});
	}

	loadFromCache()
	{
		let promise = new BX.Promise();

		let sets = [];
		let smiles = [];

		let timer = new Date();

		this.db.transaction('r', this.db.sets, this.db.smiles, () =>
		{
			this.db.sets.each(set => {
				return this.db.smiles.where('setId').equals(set.id).first().then(smile => {
					sets.push({...set, image: smile.image});
				}).catch(error => promise.reject(error));
			}).then(() => {
				return this.db.smiles.where('setId').equals(sets[0].id).each(smile => {
					smiles.push(smile);
				});
			}).then(() => {
				let promiseResult = {sets, smiles};
				promise.resolve(promiseResult);
			}).catch(error => promise.reject(error));
		});

		return promise;
	}

	loadFromServer()
	{
		let promise = new BX.Promise();
		let timer = new Date();

		this.restClient.callMethod('smile.get').then(result =>
		{
			let sets = [];
			let smiles = [];

			let answer = result.data();

			let setImage = {};

			answer.smiles = answer.smiles.map(function(smile){
				if (!setImage[smile.setId])
				{
					setImage[smile.setId] = smile.image;
				}

				let originalWidth = smile.width;
				if (smile.definition == 'HD')
				{
					originalWidth = originalWidth*2;
				}
				else if (smile.definition == 'UHD')
				{
					originalWidth = originalWidth*4;
				}

				let originalHeight = smile.height;
				if (smile.definition == 'HD')
				{
					originalHeight = originalHeight*2;
				}
				else if (smile.definition == 'UHD')
				{
					originalHeight = originalHeight*4;
				}

				return {...smile, originalWidth, originalHeight}
			});

			answer.sets.forEach(set => {
				sets.push({...set, image: setImage[set.id]});
			});

			answer.smiles.forEach(smile => {
				if (smile.setId == sets[0].id)
				{
					smiles.push(smile);
				}
			});

			let promiseResult = {sets, smiles};

			promise.resolve(promiseResult);

			this.db.smiles.clear().then(() => {
				return this.db.sets.clear().then(() => {
					this.db.sets.bulkAdd(sets);
					this.db.smiles.bulkAdd(answer.smiles);
				}).catch(error => promise.reject(error));
			}).catch(error => promise.reject(error));

		}).catch(error => promise.reject(error));

		return promise;
	}

	changeSet(setId)
	{
		let promise = new BX.Promise();

		this.db.smiles.where('setId').equals(setId).toArray(smiles => {
			promise.resolve(smiles);
		}).catch(error => promise.reject(error));

		return promise;
	}
}

