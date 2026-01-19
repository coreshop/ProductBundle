/**
 * CoreShop ProductBundle Studio Plugin
 *
 * This source file is available under the terms of the
 * CoreShop Commercial License (CCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) CoreShop GmbH (https://www.coreshop.com)
 * @license    CoreShop Commercial License (CCL)
 */

import React from 'react'
import { Tabs, Modal, Input, Form, Button, Typography, Space } from 'antd'
import { PlusOutlined, SettingOutlined, SearchOutlined, ThunderboltOutlined, TagOutlined } from '@ant-design/icons'
import { container } from '@pimcore/studio-ui-bundle'
import { ConditionsPanel } from '@coreshop/rule/src/rules/components/ConditionsPanel'
import { ActionsPanel } from '@coreshop/rule/src/rules/components/ActionsPanel'
import type { RuleCondition, RuleAction } from '@coreshop/rule/src/rules/types'
import { useTranslation } from 'react-i18next'
import type { ProductSpecificPriceRule, ProductSpecificPriceRulesData } from '../types'
import { coreshopProductServiceIds } from '../../product-price-rules/service-ids'
import { SettingsForm } from './SettingsForm'

interface Props {
  value: ProductSpecificPriceRulesData
  onChange: (value: ProductSpecificPriceRulesData) => void
  disabled?: boolean
  currentLocale?: string
  locales?: string[]
}

/**
 * Generate tab label with name, priority, and active status
 */
const generateTabLabel = (rule: ProductSpecificPriceRule, t: (key: string, opts?: any) => string): React.ReactNode => {
  return (
    <Space size={4}>
      <TagOutlined style={{ color: '#ff6600' }} />
      <span>{rule.name || t('coreshop_new_rule', { defaultValue: 'New Rule' })}</span>
      {rule.priority !== undefined && rule.priority > 0 && (
        <Typography.Text type="secondary" style={{ fontSize: 11 }}>
          (Prio: {rule.priority})
        </Typography.Text>
      )}
      {!rule.active && (
        <span style={{
          display: 'inline-block',
          width: 8,
          height: 8,
          backgroundColor: '#ff4d4f',
          borderRadius: '50%',
          marginLeft: 4
        }} title={t('inactive', { defaultValue: 'Inactive' })} />
      )}
    </Space>
  )
}

export const ProductSpecificPriceRulesPanel: React.FC<Props> = ({
  value,
  onChange,
  disabled = false,
  currentLocale = 'en',
  locales = ['en', 'de']
}) => {
  const { t } = useTranslation()
  const [activeRuleKey, setActiveRuleKey] = React.useState<string | undefined>(
    value.rules.length > 0 ? '0' : undefined
  )
  const [addModalVisible, setAddModalVisible] = React.useState(false)
  const [newRuleName, setNewRuleName] = React.useState('')

  // Check if registries are available
  const hasConditionRegistry = React.useMemo(() => {
    try {
      return container.isBound(coreshopProductServiceIds.productSpecificPriceRuleConditionRegistry)
    } catch (e) {
      console.warn('Product specific price rules condition registry not available:', e)
      return false
    }
  }, [])

  const hasActionRegistry = React.useMemo(() => {
    try {
      return container.isBound(coreshopProductServiceIds.productSpecificPriceRuleActionRegistry)
    } catch (e) {
      console.warn('Product specific price rules action registry not available:', e)
      return false
    }
  }, [])

  // Get available types from backend
  const availableConditionTypes = React.useMemo(() => {
    return value.conditions || []
  }, [value.conditions])

  const availableActionTypes = React.useMemo(() => {
    return value.actions || []
  }, [value.actions])

  const handleRuleChange = (index: number, updatedRule: ProductSpecificPriceRule) => {
    const newRules = [...value.rules]
    newRules[index] = updatedRule
    onChange({ ...value, rules: newRules })
  }

  const handleFieldChange = (index: number, field: keyof ProductSpecificPriceRule, fieldValue: any) => {
    const rule = value.rules[index]
    if (!rule) return
    handleRuleChange(index, { ...rule, [field]: fieldValue })
  }

  const handleAddRule = () => {
    if (!newRuleName.trim()) return
    const newRule: ProductSpecificPriceRule = {
      name: newRuleName,
      active: true,
      priority: 0,
      inherit: false,
      conditions: [],
      actions: []
    }
    const newRules = [...value.rules, newRule]
    onChange({ ...value, rules: newRules })
    setActiveRuleKey(String(newRules.length - 1))
    setNewRuleName('')
    setAddModalVisible(false)
  }

  const handleDeleteRule = (index: number) => {
    Modal.confirm({
      title: t('coreshop_delete_rule', { defaultValue: 'Delete Rule?' }),
      content: t('coreshop_delete_rule_confirm', {
        defaultValue: `Are you sure you want to delete "${value.rules[index]?.name}"?`,
        name: value.rules[index]?.name
      }),
      onOk: () => {
        const newRules = value.rules.filter((_, i) => i !== index)
        onChange({ ...value, rules: newRules })

        // Adjust active key
        if (activeRuleKey === String(index)) {
          setActiveRuleKey(newRules.length > 0 ? '0' : undefined)
        } else if (Number(activeRuleKey) > index) {
          setActiveRuleKey(String(Number(activeRuleKey) - 1))
        }
      }
    })
  }

  // Build sub-tabs for a single rule
  const buildRuleSubTabs = (rule: ProductSpecificPriceRule, ruleIndex: number) => {
    return [
      {
        key: 'settings',
        label: (
          <Space size={4}>
            <SettingOutlined />
            {t('settings', { defaultValue: 'Settings' })}
          </Space>
        ),
        children: (
          <SettingsForm
            rule={rule}
            onChange={(updatedRule) => handleRuleChange(ruleIndex, updatedRule)}
            currentLocale={currentLocale}
            locales={locales}
          />
        )
      },
      {
        key: 'conditions',
        label: (
          <Space size={4}>
            <SearchOutlined />
            {t('coreshop_conditions', { defaultValue: 'Conditions' })}
          </Space>
        ),
        children: hasConditionRegistry ? (
          <div style={{ padding: 16 }}>
            <ConditionsPanel
              conditions={rule.conditions as RuleCondition[]}
              availableTypes={availableConditionTypes}
              onChange={(conditions: RuleCondition[]) => handleFieldChange(ruleIndex, 'conditions', conditions)}
              registryId={coreshopProductServiceIds.productSpecificPriceRuleConditionRegistry}
            />
          </div>
        ) : (
          <div style={{ padding: 16 }}>
            <Typography.Text type="secondary">
              {t('coreshop_conditions_not_available', { defaultValue: 'Conditions not available' })}
            </Typography.Text>
          </div>
        )
      },
      {
        key: 'actions',
        label: (
          <Space size={4}>
            <ThunderboltOutlined />
            {t('coreshop_actions', { defaultValue: 'Actions' })}
          </Space>
        ),
        children: hasActionRegistry ? (
          <div style={{ padding: 16 }}>
            <ActionsPanel
              actions={rule.actions as RuleAction[]}
              availableTypes={availableActionTypes}
              onChange={(actions: RuleAction[]) => handleFieldChange(ruleIndex, 'actions', actions)}
              registryId={coreshopProductServiceIds.productSpecificPriceRuleActionRegistry}
            />
          </div>
        ) : (
          <div style={{ padding: 16 }}>
            <Typography.Text type="secondary">
              {t('coreshop_actions_not_available', { defaultValue: 'Actions not available' })}
            </Typography.Text>
          </div>
        )
      }
    ]
  }

  // Build main rule tabs
  const ruleTabItems = value.rules.map((rule, index) => ({
    key: String(index),
    label: generateTabLabel(rule, t),
    closable: !disabled,
    children: (
      <Tabs
        defaultActiveKey="settings"
        items={buildRuleSubTabs(rule, index)}
        size="small"
      />
    )
  }))

  // Handle tab edit (close)
  const onTabEdit = (targetKey: React.MouseEvent | React.KeyboardEvent | string, action: 'add' | 'remove') => {
    if (action === 'remove' && typeof targetKey === 'string') {
      handleDeleteRule(Number(targetKey))
    }
  }

  return (
    <div style={{ minHeight: 400 }}>
      {/* Header with title and add button */}
      <div style={{
        display: 'flex',
        justifyContent: 'space-between',
        alignItems: 'center',
        marginBottom: 8,
        padding: '8px 0'
      }}>
        <Typography.Text strong>
          {t('coreshop_product_specific_price_rules', { defaultValue: 'Product Specific Price Rules' })}
        </Typography.Text>
        {!disabled && (
          <Button
            type="primary"
            size="small"
            icon={<PlusOutlined />}
            onClick={() => setAddModalVisible(true)}
          />
        )}
      </div>

      {/* Rule tabs */}
      {value.rules.length > 0 ? (
        <Tabs
          type="editable-card"
          activeKey={activeRuleKey}
          onChange={setActiveRuleKey}
          onEdit={onTabEdit}
          items={ruleTabItems}
          hideAdd
          size="small"
        />
      ) : (
        <div style={{
          padding: 40,
          textAlign: 'center',
          background: '#fafafa',
          border: '1px dashed #d9d9d9',
          borderRadius: 4
        }}>
          <Typography.Text type="secondary">
            {t('coreshop_no_specific_price_rules', { defaultValue: 'No price rules defined' })}
          </Typography.Text>
          {!disabled && (
            <div style={{ marginTop: 16 }}>
              <Button
                type="primary"
                icon={<PlusOutlined />}
                onClick={() => setAddModalVisible(true)}
              >
                {t('coreshop_add_price_rule', { defaultValue: 'Add Price Rule' })}
              </Button>
            </div>
          )}
        </div>
      )}

      {/* Add rule modal */}
      <Modal
        title={t('coreshop_add_price_rule', { defaultValue: 'Add Price Rule' })}
        open={addModalVisible}
        onOk={handleAddRule}
        onCancel={() => {
          setAddModalVisible(false)
          setNewRuleName('')
        }}
        okButtonProps={{ disabled: !newRuleName.trim() }}
      >
        <Form layout="vertical">
          <Form.Item
            label={t('coreshop_name', { defaultValue: 'Name' })}
            required
          >
            <Input
              value={newRuleName}
              onChange={(e) => setNewRuleName(e.target.value)}
              placeholder={t('coreshop_enter_rule_name', { defaultValue: 'Enter rule name' })}
              onPressEnter={() => newRuleName.trim() && handleAddRule()}
              autoFocus
            />
          </Form.Item>
        </Form>
      </Modal>
    </div>
  )
}
