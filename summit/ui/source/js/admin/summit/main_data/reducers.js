/**
 * Copyright 2017 OpenStack Foundation
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 **/

import {UPDATE_SUMMIT, SUMMIT_UPDATED} from './actions';

export const summitMainData = (
    state = {
        loading: false,
    },
    action = {}
) => {
    switch(action.type){
        case UPDATE_SUMMIT:
        {
            return {
                ...state,
                loading: true,
            }
        }
        break;
        case SUMMIT_UPDATED:
        {
            const { response } = action.payload;
            return {
                ...state,
                loading: false
            }
        }
        break;
        default:
            return state;
    }
};